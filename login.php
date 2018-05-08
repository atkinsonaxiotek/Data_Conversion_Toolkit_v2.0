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

                    /*
                     * PHP-Auth (https://github.com/delight-im/PHP-Auth)
                     * Copyright (c) delight.im (https://www.delight.im/)
                     * Licensed under the MIT License (https://opensource.org/licenses/MIT)
                     */

                    /*
                     * WARNING:
                     *
                     * Do *not* use these files from the `tests` directory as the foundation
                     * for the usage of this library in your own code. Instead, please follow
                     * the `README.md` file in the root directory of this project.
                     */

                $title = "Login";
                echo '<title>' . $title . '</title>';
                
                    // enable error reporting
                    \error_reporting(\E_ALL);
                    \ini_set('display_errors', 'stdout');

                    // enable assertions
                    \ini_set('assert.active', 1);
                    @\ini_set('zend.assertions', 1);
                    \ini_set('assert.exception', 1);

                    \header('Content-type: text/html; charset=utf-8');

                    require '/volume1/web/PHP-Auth-master/vendor/autoload.php';

                    $db = new \PDO('mysql:dbname=axiotek_authtest;host=netdrv03;charset=utf8mb4', 'datkinson', 'SON@fkin2017.');

                    $auth = new \Delight\Auth\Auth($db);

                    $result = \processRequestData($auth);

                    //$_SESSION["bDebug"] = False;
                    
                    //echo '$auth->isLoggedIn()' . "\t\t\t";
                    //\var_dump($auth->isLoggedIn());
                    //echo '</br> </br>';

                    function processRequestData(\Delight\Auth\Auth $auth) {
                            if (isset($_POST)) {
                                    if (isset($_POST['action'])) {
                                            if ($_POST['action'] === 'login') {
                                                    if ($_POST['remember'] == 1) {
                                                            // keep logged in for one year
                                                            $rememberDuration = (int) (60 * 60 * 24 * 365.25);
                                                    }
                                                    else {
                                                            // do not keep logged in after session ends
                                                            $rememberDuration = null;
                                                    }
                                                    if ($_POST['bDebug'] == 1) {
                                                            $_SESSION["bDebug"] = True;
                                                    }
                                                    else {
                                                            $_SESSION["bDebug"] = False;
                                                    }
                                                    try {
                                                            if (isset($_POST['email'])) {
                                                                    $auth->login($_POST['email'], $_POST['password'], $rememberDuration);
                                                            }
                                                            elseif (isset($_POST['username'])) {
                                                                    $auth->loginWithUsername($_POST['username'], $_POST['password'], $rememberDuration);
                                                            }
                                                            else {
                                                                    return 'either email address or username required';
                                                            }

                                                            return 'ok';
                                                    }
                                                    catch (\Delight\Auth\InvalidEmailException $e) {
                                                            return 'wrong email address';
                                                    }
                                                    catch (\Delight\Auth\UnknownUsernameException $e) {
                                                            return 'unknown username';
                                                    }
                                                    catch (\Delight\Auth\AmbiguousUsernameException $e) {
                                                            return 'ambiguous username';
                                                    }
                                                    catch (\Delight\Auth\InvalidPasswordException $e) {
                                                            return 'wrong password';
                                                    }
                                                    catch (\Delight\Auth\EmailNotVerifiedException $e) {
                                                            return 'email address not verified';
                                                    }
                                                    catch (\Delight\Auth\TooManyRequestsException $e) {
                                                            return 'too many requests';
                                                    }
                                            }
                                    }
                            }
                            return null;
                    }

                            function showGuestUserForm() {

                            echo '<form action="" method="post" accept-charset="utf-8">';
                            echo '<input type="hidden" name="action" value="login" />';
                            echo '<input type="text" name="username" placeholder="Username" /> ';
                            echo '</br>';
                            echo '<input type="password" name="password" placeholder="Password" /> ';
                            echo '</br>';
                            echo '<select name="bDebug" size="1">';
                            echo '<option value="0" SELECTED>Debug Mode? — No</option>';
                            echo '<option value="1">Debug Mode? — Yes</option>';
                            echo '</select> ';
                            echo '</br>';
                            /*echo '<select name="remember" size="1">';
                            echo '<option value="0">Remember (keep logged in)? — No</option>';
                            echo '<option value="1">Remember (keep logged in)? — Yes</option>';
                            echo '</select> ';*/
                            echo '<input type="hidden" name="remember" value="0" />';
                            echo '<button type="submit">Log in</button>';
                            echo '</form>';
                            }
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
								<h2>Welcome.</h2>
								<p>Login to get started</p>
							</div>
							<div class="5u 12u(medium)">
								<ul>
                                                                    <?php
                                                                        if ($auth->isLoggedIn() === false) {
                                                                                \showGuestUserForm($auth);
                                                                        }
                                                                        else {
                                                                                //echo '$auth->isLoggedIn()' . "\t\t\t";
                                                                                //\var_dump($auth->isLoggedIn());
                                                                                header("Location: http://netdrv03/voanational-dev02/index.php", true, 301);
                                                                                exit();
                                                                        }
                                                                    ?>
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