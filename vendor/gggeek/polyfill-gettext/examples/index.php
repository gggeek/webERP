<html lang="en">
<head>
<title>Polyfill-Gettext examples</title>
</head>
<body>
<h1>Polyfill-Gettext</h1>

<h2>Introduction</h2>
<p>Polyfill-Gettext provides a simple gettext replacement that works independently from the system's gettext abilities.
It can read MO files and use them for translating strings.</p>
<p>This version has the ability to cache all strings and translations to speed up the string lookup.
While the cache is enabled by default, it can be switched off with the second parameter in the constructor (e.g. when using very large MO files
that you don't want to keep in memory)</p>


<h2>Examples</h2>
<ul>
	<li><a href="pigs_dropin.php">Polyfill-Gettext as a drop-in replacement</a></li>
	<li><a href="pigs_fallback.php">Polyfill-Gettext as a fallback solution</a></li>
</ul>

<hr />
<p>Copyright (c) 2003-2006 Danilo Segan</p>
<p>Copyright (c) 2005-2006 Steven Armstrong</p>

</body>
</html>
