<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN">
<html dir="{dir}">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
{meta}<link href="{datapath}share/icons/miscellaneous/estats.png" rel="shortcut icon" type="image/png">
<title>eStats :: {title}</title>
<!--
Simple theme for eStats 5.0
Author: Emdek
URL: http://estats.emdek.pl
Licence: GPL
-->
</head>
<body bgcolor="#EEEEEE" align="center">
<div>
<a name="top"></a>
<div align="right">
<h1 align="center">
{header}
</h1>
<!--start:selectform--><form action="{selfpath}" method="post">
<div>
{selectlocale}{selecttheme}<input type="submit" value="{lang_change}">
</div>
</form>
<!--end:selectform-->{date}<!--start:!installation--><br>
<a href="<!--start:loggedin-->{selfpath}{separator}logout">{lang_logout}<!--end:loggedin--><!--start:!loggedin-->{path}login{suffix}">{lang_login}<!--end:!loggedin--></a>
<!--end:!installation--></div>
<div align="left">
<!--start:menu--><hr>
<ul>
{menu}</ul>
<!--end:menu--><hr>
<!--start:announcements-->{announcements}{debug}<hr>
<!--end:announcements--><!--start:!antiflood--><h2 align="center">
{title}
</h2>
<!--end:!antiflood-->{page}<hr>
</div>
<div align="center">
Powered by<br>
<a href="http://estats.emdek.pl/">
<img src="{datapath}share/antipixels/default/simple.png" alt="eStats" title="eStats" border="0">
</a><br><br>
&copy; 2005 - 2012 <a href="http://emdek.pl/"><strong>Emdek</strong></a>
<div align="right">
<a href="#top" title="{lang_gototop}" id="gototop"><b>^</b></a><br>
</div>
<small>{pagegeneration}</small>
</div>
</div>
<script type="text/javascript" src="{path}{separator}checkversion&amp;script"></script>
</body>
</html>