<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Mosaddek">
    <meta name="keyword" content="FlatLab, Dashboard, Bootstrap, Admin, Template, Theme, Responsive, Fluid, Retina">
    <link rel="shortcut icon" href="img/favicon.png">

    <title>一起吃 - 后台登录</title>

    <?php
      // Don't change the css&script loading sequence
      echo $this->Html->css(array('bootstrap.min.css', 'bootstrap-reset.css', 'style.css', 'style-responsive.css'));
    ?>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 tooltipss and media queries -->
    <!--[if lt IE 9]>
    <script src="js/html5shiv.js"></script>
    <script src="js/respond.min.js"></script>
    <![endif]-->
</head>

  <body class="login-body">

    <div class="container">
      
      <?php echo $this->Form->create('User',array('class'=>'form-signin','inputDefaults'=>array('label'=>false)));?>
        <h2 class="form-signin-heading">Eatogether - Login</h2>
        <div class="login-wrap">
            <?php echo $this->Form->input('username',array('class'=>'form-control', 'placeholder'=>'User ID'));?>
            <?php echo $this->Form->input('password',array('class'=>'form-control', 'placeholder'=>'Password'));?>
            <label class="checkbox">
                <span>
                  <?php echo $this->Session->flash(); ?>
                </span>
            </label>
            <?php echo $this->Form->submit('Sign in',array('class'=>'btn btn-lg btn-login btn-block'))?>
        </div>
      <?php echo $this->Form->end();?>
    </div>

    <?php
      echo $this->Html->script(array('bootstrap.min.js', 'jquery-1.8.3.min.js'));
    ?>
  </body>
</html>
