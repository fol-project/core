<?php
$this->wrapper('template3.php');

$this->start('title');
echo '<h1>Hello world</h1>';
$this->end();

$this->start('footer');
echo '<footer>Bye</footer>';
$this->end();

echo '<p>Hello world</p>';
?>