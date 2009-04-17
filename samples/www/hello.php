<?php
$page = new tgif_page();
$page->assign('greeting',_('Hello, %s'));
$page->assign('person','world');
$page->render('hello.php');
?>
