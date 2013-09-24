<?php
/*
  This code is part of FusionDirectory (http://www.fusiondirectory.org/)
  Copyright (C) 2013  FusionDirectory

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA.
*/

/*!
 * \file jsonrpc.php
 * \brief This file is a webservice for FusionDirectory
 *
 * It's a JSON-RPC service usually used through HTTPS. It should be put in the html folder of FusionDirectory
 * Then the url of the webservice will be the url of your FusionDirectory instance followed by /jsonrpc.php
 */

function authenticateHeader($message = 'Authentication required')
{
  header('WWW-Authenticate: Basic realm="FusionDirectory"');
  header('HTTP/1.0 401 Unauthorized');
  echo "$message\n";
  exit;
}

require_once("../include/php_setup.inc");
require_once("functions.inc");
require_once("variables.inc");
require_once("jsonrpcphp/jsonRPCServer.php");

function initiateRPCSession($id = NULL, $ldap = NULL, $user = NULL, $pwd = NULL)
{
  global $config, $class_mapping, $BASE_DIR, $ui;

  session::start($id);

  /* Reset errors */
  reset_errors();
  /* Check if CONFIG_FILE is accessible */
  if (!is_readable(CONFIG_DIR."/".CONFIG_FILE)) {
    die(sprintf(_("FusionDirectory configuration %s/%s is not readable. Aborted."), CONFIG_DIR, CONFIG_FILE));
  }

  /* Initially load all classes */
  $class_list = get_declared_classes();
  foreach ($class_mapping as $class => $path) {
    if (!in_array($class, $class_list)) {
      if (is_readable("$BASE_DIR/$path")) {
        require_once("$BASE_DIR/$path");
      } else {
        die(sprintf(_("Cannot locate file '%s' - please run '%s' to fix this"),
              "$BASE_DIR/$path", "<b>fusiondirectory-setup</b>"));
      }
    }
  }
  /* Parse configuration file */
  if (session::global_is_set('LOGIN') && session::global_is_set('config') && session::global_is_set('plist')) {
    $config = session::global_get('config');
    $plist  = session::global_get('plist');
    $ui     = $plist->ui;
  } else {
    $config = new config(CONFIG_DIR."/".CONFIG_FILE, $BASE_DIR);
    if ($ldap === NULL) {
      $config->set_current(key($config->data['LOCATIONS']));
    } elseif (isset($config->data['LOCATIONS'][$ldap])) {
      $config->set_current($ldap);
    } else {
      authenticateHeader('Invalid LDAP specified');
    }
    session::global_set('DEBUGLEVEL', 0);

    if (($ssl != "") &&
          (($config->get_cfg_value('webserviceForceSSL', 'TRUE') == 'TRUE') ||
           ($config->get_cfg_value("forcessl") == "TRUE"))) {
      header ("Location: $ssl");
      exit;
    }

    if (!isset($_SERVER['PHP_AUTH_USER']) && ($user === NULL)) {
      authenticateHeader();
    }
    if ($user !== NULL) {
      $ui = ldap_login_user($user, $pwd);
    } else {
      $ui = ldap_login_user($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    }
    if (!$ui) {
      authenticateHeader('Invalid user or pwd '.$_SERVER['PHP_AUTH_USER'].'/'.$_SERVER['PHP_AUTH_PW']);
    }
    session::global_set('LOGIN', TRUE);
    $plist = new pluglist($config, $ui);
    session::global_set('plist', $plist);
    $config->loadPlist($plist);
    $config->get_departments();
    $config->make_idepartments();
    session::global_set('config', $config);
  }
}

/*!
 * \brief This class is the JSON-RPC webservice of FusionDirectory
 *
 * It must be served through jsonRPCServer::handle
 * */
class fdRPCService
{
  public $ldap;

  function __construct ()
  {
  }

  /* Documentation for RPC methods */
  /*!
   * \fn listLdaps
   * \brief Get the list of configured LDAP servers
   *
   * \return The list of configured LDAP servers as an associative array (keys are ids, values are displayable names)
   */
  /*!
   * \fn login
   * \brief Login into the webservice
   *
   * \param string $ldap the id of the LDAP server to use (can be NULL, in which case the first LDAP server found is used)
   * \param string $user The user to log in with
   * \param string $pwd The password for this user
   *
   * \return A session ID on success
   */
  function __call($method, $params)
  {
    if (preg_match('/^_(.*)$/', $method, $m)) {
      throw new Exception("Non existing method '$m[1]'");
    }

    if ($method == 'listLdaps') {
      $config = new config(CONFIG_DIR."/".CONFIG_FILE, $BASE_DIR);
      $ldaps = array();
      foreach ($config->data['LOCATIONS'] as $id => $location) {
        $ldaps[$id] = $location['NAME'];
      }
      return $ldaps;
    } elseif ($method == 'login') {
      /* Login method have the following parameters: LDAP, user, password */
      initiateRPCSession(NULL, array_shift($params), array_shift($params), array_shift($params));
      $method = 'getId';
    } else {
      initiateRPCSession(array_shift($params));
    }

    global $config;
    $this->ldap = $config->get_ldap_link();
    if (!$this->ldap->success()) {
      die('Ldap error: '.$this->ldap->get_error());
    }
    $this->ldap->cd($config->current['BASE']);

    new log("debug", "JSON-RPC", 'Method '.$method, array(), "Params:".print_r($params, TRUE));

    return call_user_func_array(array($this, '_'.$method), $params);
  }

  protected function checkAccess($type, $tab = NULL)
  {
    $infos = objects::infos($type);
    $plist = session::global_get('plist');
    if (!$plist->check_access($infos['aclCategory'])) {
      throw new Exception("Unsufficient rights for accessing type '$type'");
    }
    if ($tab !== NULL) {
      $pInfos = pluglist::pluginInfos($tab);
      if (!$plist->check_access(join(',', $pInfos['plCategory']))) {
        throw new Exception("Unsufficient rights for accessing tab '$tab' of type '$type'");
      }
    }
  }

  /*!
   * \fn ls
   * \brief Get list of object of objectType $type in $ou
   *
   * \param string  $type the objectType to list
   * \param mixed   $attrs The attributes to fetch.
   * If this is a single value, the resulting associative array will have for each dn the value of this attribute.
   * If this is an array, the keys must be the wanted attributes, and the values can be either 1, '*' or 'raw'
   *  depending if you want a single value or an array of values. 'raw' means untouched LDAP value and is only useful for dns.
   *  Other values are considered to be 1.
   * \param string  $ou the LDAP branch to search in, base will be used if it is NULL
   * \param string  $filter an additional filter to use in the LDAP search.
   *
   * \return The list of objects as an associative array (keys are dns)
   */
  protected function _ls ($type, $attrs = NULL, $ou = NULL, $filter = '')
  {
    $this->checkAccess($type);
    return objects::ls($type, $attrs, $ou, $filter);
  }

  /*!
   * \fn count
   * \brief Get count of objects of objectType $type in $ou
   *
   * \param string  $type the objectType to list
   * \param string  $ou the LDAP branch to search in, base will be used if it is NULL
   * \param string  $filter an additional filter to use in the LDAP search.
   *
   * \return The number of objects of type $type in $ou
   */
  protected function _count ($type, $ou = NULL, $filter = '')
  {
    $this->checkAccess($type);
    return objects::count($type, $ou, $filter);
  }

  /*!
   * \fn infos
   * \brief Get information about objectType $type
   *
   * \param string  $type the object type
   *
   * \return The informations on this type as an associative array
   */
  protected function _infos($type)
  {
    $this->checkAccess($type);

    global $config;
    $infos = objects::infos($type);
    unset($infos['tabClass']);
    $infos['tabs'] = $config->data['TABS'][$infos['tabGroup']];
    unset($infos['tabGroup']);
    return $infos;
  }

  /*!
   * \fn listTypes
   * \brief List existing object types
   *
   * \return An associative array with types as keys and their names as values
   */
  protected function _listTypes()
  {
    global $config;
    $types  = objects::types();

    $result = array();
    foreach ($types as $type) {
      $infos          = objects::infos($type);
      $result[$type]  = $infos['name'];
    }
    return $result;
  }

  /*!
   * \fn fields
   * \brief Get all fields from an object type
   *
   * \param string  $type the object type
   * \param string  $dn   the object to load values from if any
   * \param string  $tab  the tab to show if not the main one
   *
   * \return All attributes organized as sections
   */
  protected function _fields($type, $dn = NULL, $tab = NULL)
  {
    $this->checkAccess($type, $tab);

    if ($dn === NULL) {
      $tabobject = objects::create($type);
    } else {
      $tabobject = objects::open($dn, $type);
    }
    if ($tab === NULL) {
      $object = $tabobject->getBaseObject();
    } else {
      $object = $tabobject->by_object[$tab];
    }
    if (is_subclass_of($object, 'simplePlugin')) {
      $fields = $object->attributesInfo;
      foreach ($fields as &$section) {
        $attributes = array();
        foreach ($section['attrs'] as $key => $attr) {
          if ($object->acl_is_readable($attr->getAcl())) {
            $attr->serializeAttribute($attributes);
          }
        }
        $section['attrs'] = $attributes;
      }
      unset($section);
      return $fields;
    } else {
      /* fallback for old plugins */
      $fields = array('main' => array('attrs' => array(), 'name' => _('Plugin')));
      foreach ($object->attributes as $attr) {
        if ($object->acl_is_readable($attr.'Acl')) {
          $fields['main']['attrs'][$attr] = array(
            'value'       => $object->$attr,
            'required'    => FALSE,
            'disabled'    => FALSE,
            'label'       => $attr,
            'type'        => 'OldPluginAttribute',
            'description' => '',
          );
        }
      }
      return $fields;
    }
  }

  /*!
   * \fn update
   * \brief Update values of an object's attributes
   *
   * \param string  $type the object type
   * \param string  $dn   the object to load values from if any (otherwise it's a creation)
   * \param string  $tab  the tab to modify if not the main one
   * \param array   $values  the values as an associative array. Keys should be the same returned by fields
   *
   * \return An array with errors if any, the resulting object dn otherwise
   */
  protected function _update($type, $dn, $tab, $values)
  {
    $this->checkAccess($type, $tab);

    if ($dn === NULL) {
      $tabobject = objects::create($type);
    } else {
      $tabobject = objects::open($dn, $type);
    }
    if ($tab === NULL) {
      $tab = $tabobject->current;
    } else {
      $tabobject->current = $tab;
    }
    $_POST                  = $values;
    $_POST[$tab.'_posted']  = TRUE;
    $tabobject->save_object();
    $errors = $tabobject->check();
    if (!empty($errors)) {
      return array('errors' => $errors);
    }
    $tabobject->save();
    return $tabobject->dn;
  }

  /*!
   * \fn getId
   * \brief Get the session ID
   *
   * \return The session ID for the current session. (Mainly useless unless you log in with HTTP auth instead of login method)
   */
  protected function _getId ()
  {
    return session_id();
  }

  /*!
   * \fn getBase
   * \brief Get the LDAP base
   *
   * \return The configured LDAP base for the selected LDAP in this webservice session (see login)
   */
  protected function _getBase ()
  {
    global $config;
    return $config->current['BASE'];
  }
}

$service = new fdRPCService();
if (!jsonRPCServer::handle($service)) {
  echo "no request\n";
  echo session_id()."\n";
  print_r($_SERVER);
}
?>
