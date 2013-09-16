<!--////////////////////
  //  COUNTRY (c)
    //////////////////// -->

<table style="width:100%; vertical-align:top; text-align:left;" cellpadding=4>
 <tr>
   <td style="vertical-align:top; width:50%">
     <h2><img class="center" alt="" src="images/rightarrow.png"> {t}Properties{/t}</h2>
     <table>
      <tr>
       <td><LABEL for="c">{t}Country name{/t}</LABEL>{$must}</td>
       <td>
{render acl=$cACL}
      <input type='text' id="c" name="c" size=25 maxlength=60 value="{$c}" title="{t}Name of country to create{/t}">
{/render}
       </td>
      </tr>
      <tr>
       <td><LABEL for="description">{t}Description{/t}</LABEL>{$must}</td>
       <td>
{render acl=$descriptionACL}
        <input type='text' id="description" name="description" size=25 maxlength=80 value="{$description}" title="{t}Descriptive text for department{/t}">
{/render}
       </td>
      </tr>
  {if !$is_root_dse}
      <tr>
        <td><LABEL for="base">{t}Base{/t}</LABEL>{$must}</td>
        <td>
{render acl=$baseACL}
          {$base}
{/render}
    </td>
   </tr>
  {/if}

  {if $manager_enabled}
    <tr>
     <td><label for="manager">{t}Manager{/t}</label></td>
     <td>
{render acl=$managerACL}
        <input type='text' name='manager_name' id='manager_name' value='{$manager_name}' disabled
          title='{$manager}'>
{/render}
{render acl=$managerACL}
        <input type='image' src='images/lists/edit.png' name='editManager' class='center' alt="edit"/>
{/render}
        {if $manager!=""}
{render acl=$managerACL}
        <img src='images/info_small.png' title='{$manager}' class='center' alt="info"/>
{/render}
{render acl=$managerACL}
        <input type='image' src='images/lists/trash.png' name='removeManager' class='center' alt="trash"/>
{/render}
        {/if}
     </td>
    </tr>
  {/if}
  </table>
  </td>
 </tr>
</table>
<p class='seperator'>&nbsp;</p>
<input type='hidden' name='dep_generic_posted' value='1'>
