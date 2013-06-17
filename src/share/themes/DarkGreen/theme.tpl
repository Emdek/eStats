<!DOCTYPE html>
<html dir="{dir}" id="html">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
{meta}<link href="{datapath}share/themes/common/common.css" rel="stylesheet" type="text/css">
<link href="{datapath}share/themes/{theme}/theme.css" rel="stylesheet" type="text/css" title="{theme}">
<link href="{datapath}share/icons/miscellaneous/estats.png" rel="shortcut icon" type="image/png">
<title>eStats :: {title}</title>
{css}<!--
DatkGreen theme for eStats 5.0
Author: Neo, updated by Emdek
Licence: GPL
-->
<script type="text/javascript" src="{datapath}lib/functions.js"></script>
</head>
<body>
<div id="body">
<div id="content">
<div id="header">
<div id="headerright">
<!--start:selectform--><form action="{selfpath}" method="post">
<div>
{selectlocale}{selecttheme}<input type="submit" value="{lang_change}">
</div>
</form>
<!--end:selectform-->{date}<!--start:!installation--><br>
<a href="<!--start:loggedin-->{selfpath}{separator}logout">{lang_logout}<!--end:loggedin--><!--start:!loggedin-->{path}login{suffix}">{lang_login}<!--end:!loggedin--></a>
<!--end:!installation--></div>
<h1>
{header}
</h1>
<!--start:menu--><ul>
{menu}</ul>
<!--end:menu--></div>
{announcements}{debug}<!--start:!antiflood--><h2>
{title}
</h2>
<!--end:!antiflood-->{page}</div>
<div id="preloader">
<img src="{datapath}share/themes/{theme}/images/menu.png" alt="">
<img src="{datapath}share/themes/{theme}/images/menu_active.png" alt="">
<img src="{datapath}share/themes/{theme}/images/gototop_active.png" alt="">
</div>
<div id="footer">
<div>
Powered by<br>
<a href="http://estats.emdek.pl/">
<img src="{datapath}share/antipixels/default/darkgreen.png" alt="eStats" title="eStats">
</a>
<br>
<div>
Design by <strong>Neo</strong><br>
&copy; 2005 - 2012 <a href="http://emdek.pl/"><strong>Emdek</strong></a>
</div>
<a href="#header" title="{lang_gototop}" id="gototop">&nbsp;</a>
<small>{pagegeneration}</small>
</div>
</div>
</div>
<script type="text/javascript" src="{path}{separator}checkversion&amp;script"></script>
</body>
</html>