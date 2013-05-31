var eFlashVersion = 0;

if (navigator.plugins.length)
{
	var eFlashPlugin = navigator.plugins["Shockwave Flash"];

	if (eFlashPlugin)
	{
		if (eFlashPlugin.description)
		{
			eFlashVersion = new RegExp(/\d+(?:\.\d+)?/).exec(eFlashPlugin.description);
		}
		else
		{
			eFlashVersion = '?';
		}
	}
}
else
{
	document.write('<scr' + 'ipt language="VBScript">\n	on error resume next\n	For i = 2 to 15\n	If Not(IsObject(CreateObject("ShockwaveFlash.ShockwaveFlash." & i))) Then\n	Else\n	eFlashVersion = i\n	End If\n	Next\n</scr' + 'ipt>');
}

document.write('<a href="http://estats.emdek.cba.pl"><img src="' + ePath + 'antipixel.php?count=' + (eCount ? 1 : 0) + '&javascript=1&cookies=' + (navigator.cookieEnabled ? 1 : 0) + '&flash=' + escape(eFlashVersion) + '&java=' + (navigator.javaEnabled() ? 1 : 0) + '&width=' + screen.width + '&height=' + screen.height + '&referrer=' + escape(document.referrer) + '&address=' + escape(eAddress ? eAddress : window.location.href) + '&title=' + escape(eTitle ? eTitle : document.title) + (eAntipixel ? '&antipixel=' + escape(eAntipixel) : '') + '&' + Date.now() + '" alt="eStats" title="eStats"></a>');
