<!DOCTYPE HTML>

<html>
	<head>
                <meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<!--[if lte IE 8]><script src="assets/js/ie/html5shiv.js"></script><![endif]-->
		<link rel="stylesheet" href="assets/css/main.css" />
		<!--[if lte IE 8]><link rel="stylesheet" href="assets/css/ie8.css" /><![endif]-->
                
                
		<!-- Scripts -->

			<script src="assets/js/jquery.min.js"></script>
			<script src="assets/js/jquery.dropotron.min.js"></script>
			<script src="assets/js/skel.min.js"></script>
			<script src="assets/js/util.js"></script>
			<!--[if lte IE 8]><script src="assets/js/ie/respond.min.js"></script><![endif]-->
			<script src="assets/js/main.js"></script>
                        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
                                        
                <?php
                $title = "Welcome";
                echo '<title>' . $title . '</title>';
                //include "v_header.php";
                ?>
                
	</head>
        
	<body class="homepage">
		<div id="page-wrapper">

			<!-- Header -->
				<div id="header-wrapper">
					<header id="header" class="container">

						<!-- Logo -->
							<div id="logo">
								<img src="images/logo-national-color" alt="VOA Logo" style="width:300px;">
								<!--<span>Conversion Toolkit</span>-->
							</div>

					</header>
				</div>

			<!-- Banner -->
				<div id="banner-wrapper">
					<div id="banner" class="box container">
						<div class="row">
							<div class="7u 12u(medium)">
								<?php 
                                                                //echo $_GET['lo'];
                                                                if ($_GET['lo'] === 'true') {
                                                                    echo "<h2>Thank you for using the Conversion Toolkit.</h2>";
                                                                    echo "<p>Login again to start another session.</p>";
                                                                }
                                                                else {
                                                                    echo "<h2>Welcome to the Conversion Toolkit.</h2>";
                                                                    echo "<p>Login to get started.</p>";
                                                                }
                                                                ?>
								
							</div>
							<div class="5u 12u(medium)">
								<ul>
                                                                    <li><a href="login.php" class="button big icon fa-arrow-circle-right">Login</a></li>
								</ul>
							</div>
						</div>
					</div>
				</div>

			<!-- Features -->
			
			<!-- Main
				<div id="main-wrapper">
					<div class="container">
						<div class="row 200%">
							<div class="4u 12u(medium)">

									<div id="sidebar">
										<section class="widget thumbnails">
											<h3>Interesting stuff</h3>
											<div class="grid">
												<div class="row 50%">
													<div class="6u"><a href="#" class="image fit"><img src="images/pic04.jpg" alt="" /></a></div>
													<div class="6u"><a href="#" class="image fit"><img src="images/pic05.jpg" alt="" /></a></div>
													<div class="6u"><a href="#" class="image fit"><img src="images/pic06.jpg" alt="" /></a></div>
													<div class="6u"><a href="#" class="image fit"><img src="images/pic07.jpg" alt="" /></a></div>
												</div>
											</div>
											<a href="#" class="button icon fa-file-text-o">More</a>
										</section>
									</div>

							</div>
							
						</div>
					</div>
				</div> -->

			<!-- Footer -->
				
                                    <?php
                                        include "footer.php";
                                    ?>

			</div>

	</body>
</html>