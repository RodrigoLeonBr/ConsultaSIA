<?php
echo "Mod Rewrite Test\n";
echo "Apache modules: " . implode(', ', apache_get_modules()) . "\n";
echo "Mod rewrite enabled: " . (in_array('mod_rewrite', apache_get_modules()) ? 'YES' : 'NO') . "\n";
?>