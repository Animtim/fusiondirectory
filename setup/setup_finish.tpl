<div class='default'>
  <p>
    <b>{t}Create your configuration file{/t}</b>
  </p>
  <p> 
    {$msg2}
  </p>
      {t}Run <tt>fusiondirectory-setup --check-config</tt> to put the correct rights on the fusiondirectory.conf file.{/t}
  <p> 
    <input type='submit' name='getconf' value='{t}Download configuration{/t}'>
  </p>
    {if $err_msg != ""}
      <p class="seperator">&nbsp;</p>
      <br>
      {t}Status: {/t}
      <a style='color:red ; font-weight:bold '>{$err_msg}</a>
    {/if}

</div>
<input type='hidden' value='1' name='step8_posted'>
