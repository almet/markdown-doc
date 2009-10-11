<?php
require_once 'libs/markdown.php';

$article = 'articles/'.$_GET['article'].'.md';
$theme = (isset($_GET['theme'])) ? $_GET['theme'] : 'spiral';

if(file_exists($article)){
	$content = file_get_contents($article);
	$parser = new Markdown_Parser();

	include 'themes/'.$theme.'/_header.php';
	echo $parser->transform($content);
	include 'themes/'.$theme.'/_footer.php';
}
?>
