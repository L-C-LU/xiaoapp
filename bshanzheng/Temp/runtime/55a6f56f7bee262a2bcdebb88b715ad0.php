<?php /*a:1:{s:46:"/lafengyun/site/bshanzheng/App/Views/show.html";i:1602815088;}*/ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=gb2312" />

    <title><?php echo htmlentities($title); ?></title>
<body style="padding:40px;">
<?php foreach($list as $key=>$item): ?>
<span style="font-size:14px;font-weight:bold"><?php echo htmlentities($item['title']); ?></span>
<div style="font-size:12px;">
    <br>
    <?php echo htmlspecialchars_decode($item['content']); ?>

</div>
<?php endforeach; ?>
</body>
</html>