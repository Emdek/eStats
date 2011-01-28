<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{language}" dir="{dir}" id="html">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
{meta}<link href="{datapath}share/themes/common/common.css" rel="stylesheet" type="text/css" />
<link href="{datapath}share/themes/{theme}/theme.css" rel="stylesheet" type="text/css" media="all" title="{theme}" />
<link href="{datapath}share/themes/{theme}/print.css" rel="stylesheet" type="text/css" media="print"/>
<link href="{datapath}share/icons/miscellaneous/estats.png" rel="shortcut icon" type="image/png" />
<title>eStats :: {title}</title>
{css}<!--
Fresh theme for eStats 5.0
Author: Emdek
http://estats.emdek.cba.pl
Licence: GPL
-->
<script type="text/javascript" src="{datapath}lib/functions.js"></script>
</head>
<body>
<div id="background_left">
<div id="background_right">
<div id="content">
<div id="header">
<div id="headerright">
<!--start:selectform--><form action="{selfpath}" method="post">
<div>
{selectlocale}{selecttheme}<input type="submit" value="{lang_change}" tabindex="{selectformindex}"  />
</div>
</form>
<!--end:selectform-->{date}<!--start:!installation--><br />
<a href="<!--start:loggedin-->{selfpath}{separator}logout" tabindex="{loginindex}">{lang_logout}<!--end:loggedin--><!--start:!loggedin-->{path}login{suffix}" tabindex="{loginindex}">{lang_login}<!--end:!loggedin--></a>
<!--end:!installation--></div>
<img src="{datapath}share/themes/{theme}/images/logo.png" alt="" id="logo" />
<h1>
{header}
</h1>
<!--start:menu--><ul id="menu">
{menu}</ul>
<!--end:menu--></div>
<div id="announcements">
{announcements}{debug}</div>
{page}<div id="preloader">
{preloader}</div>
<div id="footer">
Powered by<br />
<a href="http://estats.emdek.cba.pl/" tabindex="{tabindex}">
<img src="{datapath}share/antipixels/default/fresh.png" alt="eStats" title="eStats" />
</a>
<br />
<div>
&copy; 2005 - 2011 <a href="http://emdek.cba.pl/" tabindex="{tabindex}"><strong>Emdek</strong></a>
</div>
<a href="#header" title="{lang_gototop}" tabindex="{tabindex}" id="gototop">&nbsp;</a>
<small>{pagegeneration}</small>
</div>
</div>
</div>
</div>
<script type="text/javascript" src="{path}{separator}checkversion&amp;script"></script>
</body>
</html>