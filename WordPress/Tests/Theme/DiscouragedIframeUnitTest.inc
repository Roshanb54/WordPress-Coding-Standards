<?php

echo 'This string should pass even if iframe is present.';
echo 'Sometimes iframes are not harmful.';
?>
<iframe src="http://example.com" > // Warning.
Sometime iframe is not harmful. // OK.
Sometimes iframes are not harmful. // OK.
