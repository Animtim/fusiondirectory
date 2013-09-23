<!-- Headline -->
<body onLoad="javascript:$$('div.debug_div').each(function (a) { a.hide(); });">
  {$game_screen}
  {$php_errors}
  <form action="main.php{$plug}" name="mainform" id="mainform" method="post" enctype="multipart/form-data">
    <div class="setup_header">
      <div id="header_left">
        <img id="fd_logo" class="optional" src="{filePath file="images/fd_logo.png"}" alt="FusionDirectory"/>
        <a class="maintitlebar" href="main.php?reset=1">
          <img src="{filePath file="images/home.png"}" alt="Main"/>&nbsp;{t}Main{/t}
        </a>
        &nbsp;
        <a class="maintitlebar logout" href="logout.php?request">
          <img src="{filePath file="images/sign_out.png"}" alt="Sign out"/>&nbsp;{t}Sign out{/t}
        </a>
        <a class="plugtop">
          <img src="{$headline_image}" alt="{t}{$headline}{/t}"/>{t}{$headline}{/t}
        </a>
      </div>
      <div id="header_right">
        <a><span class="optional">{t}Signed in:{/t} </span><b>{$username}</b></a>
        <div class="logout-label">
          <canvas id="sTimeout" width="22" height="22" title="{$sessionLifetime}|{t}Session expires in %d!{/t}"></canvas>
        </div>
      </div>
    </div>

      <table class="framework">
        <tbody>
          <tr>

            {if !$hideMenus}
              <!-- Menu -->
              <td class="optional" id="menucell">
                {$menu}
                <br/>
              </td>
            {/if}

              <!-- Plugin window -->
              <td id="maincell">
                <div class="plugin_window">
                  {$msg_dialogs}
                  {$contents}
                </div>
                {if $channel != ""}
                    <input type="hidden" name="_channel_" value="{$channel}"/>
                {/if}
              </td>
          </tr>
        </tbody>
      </table>

    {$errors}
    {$focus}
    <input type="hidden" name="php_c_check" value="1"/>
  </form>


  <!-- Automatic logout when session is expired -->
  <script type="text/javascript">
  {literal}
   function logout()
   {
    document.location = 'logout.php';
   }
  {/literal}

   logout.delay({$sessionLifetime});

  </script>

</body>
</html>
