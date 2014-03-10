<!DOCTYPE html>
<html>
<head>
    <title>Doorbell</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="/css/jasny-bootstrap.min.css" rel="stylesheet" media="screen">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
</head>
<body>
    <div class="container">

        <h1>Rings</h1>
        <table class="table">
            <tr>
                <th>ID</th>
                <th>Ringed at</th>
                <th>Ringtime</th>
                <th>Image</th>
            </tr>
            <?php
            foreach($this->rings as $ring) {
            echo '<tr>';
            echo '<td>' . $ring['id'] . '</td>';
            echo '<td>' . date('d/m-Y H:i:s', strtotime($ring['ringed_at'])) . '</td>';
            echo '<td>' . $ring['ringtime'] . ' sec.</td>';
            echo '<td><a href="/webcam/' . $ring['image'] . '"><img src="/webcam/' . $ring['image'] . '" border="0" height="80"></a></td>';
            echo '</tr>';
            }
            ?>
        </table>

        <?php
        if ($this->pages>0) {
            ?>
            <ul class="pagination">
                <li<?php echo $this->page==1 ? ' class="disabled"' : ''; ?>><a href="?page=1">&laquo;</a></li>
                <?php for ($pageNo = 1; $pageNo <= $this->pages; $pageNo++) { ?>
                    <li<?php echo $this->page==$pageNo ? ' class="active"' : ''; ?>><a href="?page=<?php echo $pageNo; ?>"><?php echo $pageNo; ?></a></li>
                <?php } ?>
                <li<?php echo $this->page==$this->pages ? ' class="disabled"' : ''; ?>><a href="?page=<?php echo $this->page+1; ?>">&raquo;</a></li>
            </ul>
            <?
        }
        ?>

    </div>

    <script src="/js/jquery.1.11.0.js"></script>
    <script src="/js/bootstrap.min.js"></script>
    <script src="/js/jasny-bootstrap.min.js"></script>
</body>
</html>
