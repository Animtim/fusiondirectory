<div class="contentboxh">
 <p class="contentboxh">
  <img src="images/launch.png" alt="[F]">{t}Filter{/t}
 </p>
</div>

<div class="contentboxb">

{$USER}&nbsp;<LABEL for='USER'>{t}Show users{/t}</LABEL><br>
{$GROUP}&nbsp;<LABEL for='GROUP'>{t}Show groups{/t}</LABEL><br>
{$APPLICATION}&nbsp;<LABEL for='APPLICATION'>{t}Show Applications{/t}</LABEL><br>
{$SERVER}&nbsp;<LABEL for='SERVER'>{t}Show server{/t}</LABEL><br>
{$WORKSTATION}&nbsp;<LABEL for='WORKSTATION'>{t}Show workstation{/t}</LABEL><br>
{$WINSTATION}&nbsp;<LABEL for='WINSTATION'>{t}Show Windows PC{/t}</LABEL><br>
{$TERMINAL}&nbsp;<LABEL for='TERMINAL'>{t}Show terminal{/t}</LABEL><br>

{$PRINTER}&nbsp;<LABEL for='PRINTER'>{t}Show printer{/t}</LABEL><br>
{$PHONE}&nbsp;<LABEL for='PHONE'>{t}Show phone{/t}</LABEL><br>

 <div style="border-top:1px solid #AAAAAA"></div>
 {$SCOPE}

 <table summary="" style="width:100%;border-top:1px solid #B0B0B0;">
  <tr>
   <td>
    <label for="NAME">
     <img src="images/lists/search.png"/>&nbsp;{t}Name{/t}
    </label>
   </td>
   <td>
    {$NAME}
   </td>
  </tr>
 </table>

 <table style="width:100%;background:#EEEEEE;border-top:1px solid #B0B0B0;">
  <tr>
   <td style="width:100%;text-align:right;">
    {$APPLY}
   </td>
  </tr>
 </table>
</div>
