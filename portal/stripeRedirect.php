<?php
echo "<h1>Something has gone wrong and we need your help</h1>\n";
echo "The credit card processor, Stripe, has done a redirect that the system was not expecting. Copy/paste this screen and seek assistance from registration.\n";
echo "Once reported we will look into it and try to determine how to resolve the issue. Thanks for your patience.\n";
echo "<pre>\n";
echo "Server Variables\n";
var_dump($_SERVER);
echo "\nRequest Variables\n";
var_dump($_REQUEST);
echo "\n</pre>\n";
