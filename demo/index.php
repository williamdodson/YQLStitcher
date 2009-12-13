<?php
// require the YQLStitcher class
require_once getenv("DOCUMENT_ROOT") . '/YQLStitcher/yqlstitcher.class.php';
// cache folder
$cache = getenv("DOCUMENT_ROOT") . "/cache";
// time in seconds to retain the cache file (defaults to 1 hour)
$timeout = 60;
//feeds we want to stitch together
$feeds = array(
  "http://www.w3.org/News/atom.xml",
  "http://www.alistapart.com/site/rss"
);
// return data format
$format = 'xml';
// the YQL query to run
$query = "select channel.title,channel.link,channel.item.title,channel.item.description,channel.item.link from xml";

// new YQLStitcher instance
$yql = new YQLStitcher($cache, $timeout, $feeds, $format, $query, 15, 'channel.item', true);

// grab the newsfeed items
$items = $yql->get_items();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Testing YQLStitcher</title>
</head>
<body>
  <h1>Testing YQLStitcher</h1>
  <hr />
<?php
// loop over and display the newsfeed items
foreach ($items as $item) {
  echo '<h2>Link: <a href="' . $item->channel->item->link . '">' . $item->channel->item->title . '</a></h2>';
  echo '<p><small>Published on: ' . date('Y-m-d', strtotime($item->channel->item->pubDate)) . ' | Source: <a href="' . $item->channel->link . '">' . $item->channel->title . '</a></small></p>';
  echo $item->channel->item->description;
}
?>
<hr />
<p><small>&#169; <?php echo date('Y'); ?> Outer Banks Design Works.</small></p>
</body>
</html>