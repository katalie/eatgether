<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Mosaddek">
    <meta name="keyword" content="FlatLab, Dashboard, Bootstrap, Admin, Template, Theme, Responsive, Fluid, Retina">
    <link rel="shortcut icon" href="../img/favicon.png">

    <title>厨师备菜单</title>

    <?php
      // Don't change the css&script loading sequence
      echo $this->Html->css(array('bootstrap.min.css', 'bootstrap-reset.css', 'style.css', 'style-responsive.css'));
      echo $this->Html->css(array('../assets/font-awesome/css/font-awesome.css', '../assets/advanced-datatable/media/css/demo_page.css', '../assets/advanced-datatable/media/css/demo_table.css', '../assets/data-tables/DT_bootstrap.css'));
    ?>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 tooltipss and media queries -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.js"></script>
      <script src="js/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

  <section id="container" class="">
      <!--header start-->
      <header class="header white-bg">
          <div class="sidebar-toggle-box">
              <div data-original-title="Toggle Navigation" data-placement="right" class="fa fa-bars tooltips"></div>
          </div>
          <!--logo start-->
          <a href="/eat/orders/viewByRestaurant" class="logo" >一起吃<span>Eatogether</span></a>
          <!--logo end-->
          
          <div class="top-nav ">
              <ul class="nav pull-right top-menu">
                  <!-- user login dropdown start-->
                  <li class="dropdown">
                      <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                          <img alt="" src="../img/avatar1_small.jpg">
                          <span class="username"><?php echo $user['Restaurant']['name'] ?></span>
                          <b class="caret"></b>
                      </a>
                      <ul class="dropdown-menu extended logout">
                          <div class="log-arrow-up"></div>
                          <li><a href="#"><i class=" fa fa-suitcase"></i>资料</a></li>
                          <li><a href="#"><i class="fa fa-cog"></i>设置</a></li>
                          <li><a href="#"><i class="fa fa-bell-o"></i>通知</a></li>
                          <li><a href="/eat/users/logout"><i class="fa fa-key"></i>登出</a></li>
                      </ul>
                  </li>
                  <!-- user login dropdown end -->
              </ul>
          </div>
      </header>
      <!--header end-->
      <!--sidebar start-->
      <aside>
          <div id="sidebar"  class="nav-collapse ">
              <!-- sidebar menu start-->
              <ul class="sidebar-menu" id="nav-accordion">
                  <li class="sub-menu">
                      <a href="javascript:;" class="active" >
                          <i class="fa fa-th"></i>
                          <span>餐馆信息</span>
                      </a>
                      <ul class="sub">
                          <li><a href="/eat/orders/viewByRestaurant">当前订单</a></li>
                          <li><a href="/eat/orders/viewCurrentChefList">厨师总备菜单</a></li>
                          <li class="sub-menu">
                              <a href="javascript:;" class="active">厨师分地点备菜单</a>
                              <ul class="sub">
                                <li><a href="/eat/orders/viewLocationChefList/21">University of Ottawa</a></li>
                                <li><a href="/eat/orders/viewLocationChefList/23">Carleton University</a></li>
                                <li><a href="/eat/orders/viewLocationChefList/24">Algonquin College</a></li>
                                <li><a href="javascript:;">Lees Apartments</a></li>
                              </ul>
                          </li>
                      </ul>
                  </li>
              </ul>
              <!-- sidebar menu end-->
          </div>
      </aside>
      <!--sidebar end-->
      <!--main content start-->
      <section id="main-content">
          <section class="wrapper site-min-height">
              <!-- page start-->
              <div class="row">
                  <div class="col-lg-12">
                      <section class="panel">
                          <header class="panel-heading">
                              <?php echo $location['Location']['name']?>厨师备菜单 (<?php echo $orderTime["startTime"]. ' 到 ' . $orderTime["endTime"]?>) 
                          </header>
                          <div class="panel-body">
                                <div class="adv-table">
                                    <table  class="display table table-bordered table-striped" id="example">
                                      <thead>
                                      <tr>
                                          <th>菜品名称</th>
                                          <th>菜品数量</th>
                                      </tr>
                                      </thead>
                                      <tbody>
                                      <?php foreach ($products as $key => $value) {?>
                                      <tr class="gradeX">
                                          <td><?php echo $value['Product']['name'] ?></td>
                                          <td><?php echo $value['Product']['quantity'] ?></td>
                                      </tr>
                                      <?php } ?>
                                      </tbody>
                                      <tfoot>
                                      <tr>
                                          <th>菜品名称</th>
                                          <th>菜品数量</th>
                                      </tr>
                                      </tfoot>
                          </table>
                                </div>
                          </div>
                      </section>
                  </div>
              </div>
              <!-- page end-->
          </section>
      </section>
      <!--main content end-->
      <!--footer start-->
      <footer class="site-footer">
          <div class="text-center">
              2015 &copy; Eatogether by Ottawazine Global Inc.
              <a href="#" class="go-top">
                  <i class="fa fa-angle-up"></i>
              </a>
          </div>
      </footer>
      <!--footer end-->
  </section>
    <?php
      echo $this->Html->script(array('jquery-1.8.3.min.js', 'bootstrap.min.js', 'jquery.dcjqaccordion.2.7.js', 'jquery.scrollTo.min.js', 'jquery.nicescroll.js', 'respond.min.js', 'common-scripts.js'));
      echo $this->Html->script(array('../assets/advanced-datatable/media/js/jquery.dataTables.js', '../assets/data-tables/DT_bootstrap.js'));
    ?>

    <!--script for this page only-->

      <script type="text/javascript" charset="utf-8">
          $(document).ready(function() {
              $('#example').dataTable( {
                  "aaSorting": [[ 4, "desc" ]]
              } );
          } );
      </script>
  </body>
</html>
