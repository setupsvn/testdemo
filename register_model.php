<?php

require_once 'Inf_Model.php';
require_once 'validation.php';
require_once 'product_model.php';
require_once 'Calculation.php';
require_once 'configuration_model.php';

class register_model extends Inf_Model {

    public $obj_vali;
    public $table_prefix;
    public $active;
    private $mailObj;
    public $obj_product;
    public $obj_calc;
    public $obj_config;

    public function __construct() {

        parent::__construct();
        require_once 'validation.php';
        $this->obj_vali = new Validation();
        $this->obj_product = new product_model();
        $this->obj_calc = new Calculation();
        $this->obj_config = new configuration_model();

        require_once 'Phpmailer.php';
        $this->mailObj = new PHPMailer();
    }

    public function validateRegisterData($regr, $module_status) {

        $product_status = $module_status['product_status'];

        $pin_status = $module_status['pin_status'];


        $username = $regr['username'];
        $position = $regr['position'];
        // $passcode = $regr['passcode'];
        $fatherid = $regr['fatherid'];
        $product_id = $regr['prodcut_id'];
        $flag = true;
        //for pin avail
        if ($this->isUserNameAvailable($username)) {
            $flag = false;
            echo "<script>alert('Error on registration. User already registered 69')</script>";
            echo "<script>document.location.href='../admin/home'</script>";
        } else
        if (!$this->isLegAvailable($fatherid, $position, $module_status)) { // User already registered
            $flag = false;
            echo "<script>alert('Error on registration. User already registered in this position')</script>";
            echo "<script>document.location.href='../admin/home'</script>";
        } else
        if ($product_status == 'yes') {

            if (!$this->obj_product->isProductAvailable($product_id)) {
                $flag = false;
                echo "<script>alert('Error on registration ,Product not Available')</script>";
                echo "<script>document.location.href='../admin/home'</script>";
            }
        }


        return $flag;
    }

    public function getPlacement($sponsor_id) {
        $user["0"] = $sponsor_id;
        $sponser_arr = $this->checkPosition($user);
        return $sponser_arr;
    }

    public function checkPosition($downlineuser) {

        $p = 0;
        $child_arr = "";
        for ($i = 0; $i < count($downlineuser); $i++) {
            $sponsor_id = $downlineuser["$i"];


            $this->db->select("id");
            $this->db->select("position");
            $this->db->from("ft_individual");
            $this->db->where('father_id', $sponsor_id);
            $this->db->where('active !=', 'server');
            $res = $this->db->get();
            $row_count = $res->num_rows();
            if ($row_count > 0) {
                foreach ($res->result_array() as $row) {
                    $width_ceiling = $this->getWidthCieling();
                    if ($row_count < $width_ceiling) {
                        $sponsor['id'] = $sponsor_id;
                        $sponsor['position'] = $row_count + 1;
                        return $sponsor;
                    } else {
                        $child_arr[$p] = $row["id"];
                        $p++;
                    }
                }
            } else {
                $sponsor['id'] = $sponsor_id;
                $sponsor['position'] = 1;
                return $sponsor;
            }
        }

        if (count($child_arr) > 0) {
            //print_r($child_arr);
            $position = $this->checkPosition($child_arr);

            return $position;
        }
    }

    function userNameToID($user_name) {
        return $this->obj_vali->userNameToID($user_name);
    }

    function getPlacementUnilevel($sponsor_id) {
        $position = "";
        $this->db->select("position");
        $this->db->from("ft_individual");
        $this->db->where('father_id', $sponsor_id);
        $this->db->where('active', 'server');
        $res = $this->db->get();
        foreach ($res->result() as $row) {
            $position = $row->position;
        }
        return $position;
    }

    public function viewProducts() {


        $product_array = $this->obj_product->getAllProducts('yes');
        $lang_product = $this->lang->line('select_product');
        $products = "";
        for ($i = 0; $i < count($product_array); $i++) {
            $id = $product_array["$i"]["product_id"];
            $product_name = $product_array["$i"]["product_name"];
            $product_value = $product_array["$i"]["product_value"];
            $products.="<option value='$id' >$product_name</option>";
        }
        return $products;
    }

    public function viewState($country_id = '', $lang_id = '', $option = '') {
        $state = '';

        $this->db->select('State_Id,');
        $this->db->select('State_Name');
        $this->db->where('country_id', $country_id);
        $this->db->order_by('State_Name');
        $this->db->from('life_state');
        $query = $this->db->get();

        if ($option == '') {
            $state = "<option class='form-control' value='' selected='selected'>Select State</option>";
        } else {
            $state = "<option class='form-control' value='' selected='selected'>$option</option>";
        }

        $i = 0;
        if ($query->num_rows > 0) {
            foreach ($query->result_array() as $row) {
                $State_Id = $row['State_Id'];
                $State_Name = $row['State_Name'];

                if ($option != $State_Name) {
                    $state .= "<option value='$State_Id'>$State_Name</option>";
                }
            }
        } else {
            $state .= "<option value='0'>--None--</option>";
        }

        return $state;
    }

    public function viewDistrict($state, $district) {

        $state_id = $this->getStateID($state);
        $arr = $this->getDistrict($state_id);
        echo "&nbsp;&nbsp;&nbsp;<select name='district'  id='district' style='width: 158px;' tabindex='14' onChange='setHiddenValue(this.value)' >
                  <option value='$district' selected='selected'>$district</options>";
        $cnt = count($arr);
        for ($i = 0; $i < $cnt; $i++) {
            $id = $arr["detail$i"]["district_id"];
            $name = $arr["detail$i"]["district_name"];
            if ($district != $name) {
                echo "<option value='$name'>$name</option>";
            }
        }
        echo '</select>';
    }

    public function getDistrict($state_id) {

        $this->db->select('District_Id,District_Name');
        $this->db->where('District_State_Ref_Id', $state_id);
        $this->db->from("life_district");
        $this->db->order_by('District_Name');
        $query = $this->db->get();

        $i = 0;
        foreach ($query->result_array() as $row) {
            $arr["detail$i"]['district_id'] = $row['District_Id'];
            $arr["detail$i"]['district_name'] = $row['District_Name'];
            $i++;
        }

        return $arr;
    }

    public function getStateName($state_id) {
        $State_Name = "";
        $this->db->select('State_Name');
        $this->db->from('life_state');
        $this->db->where('State_Id', $state_id);
        $query = $this->db->get();
        foreach ($query->result() as $row) {
            $State_Name = $row->State_Name;
        }
        return $State_Name;
    }

    public function isProductAdded() {

        $flag = "no";

        $this->db->select("COUNT(*) AS cnt");
        $this->db->from("product");
        $qr = $this->db->get();

        foreach ($qr->result() as $row) {
            $count = $row->cnt;
        }

        if ($count > 0)
            $flag = "yes";

        return $flag;
    }

    public function isPinAdded() {
        $flag = "no";

        $this->db->select("COUNT(*) AS cnt");
        $this->db->from("pin_numbers");
        $qr = $this->db->get();

        foreach ($qr->result() as $row) {
            $count = $row->cnt;
        }

        if ($count > 0)
            $flag = "yes";

        return $flag;
    }

    public function checkPassCode($prodcutpin, $prodcutid, $epincount) {
        require_once 'product_model.php';


        $this->obj_product = new product_model();
        $prodcutpin = mysql_real_escape_string($prodcutpin);
        if ($this->obj_product->isProductPinAvailable($prodcutid, $prodcutpin, $epincount))
            return $this->obj_product->isPasscodeAvailable($prodcutpin);
    }

    public function checkSponser($sponser_full_name, $user_id) {

        require_once 'validation.php';
        $obj_val = new Validation();
        $flag = false;

        $sponser_full_name = mysql_real_escape_string($sponser_full_name);
        $sponser_user_name = mysql_real_escape_string($user_id);

        $sponser_user_id = $obj_val->userNameToID($sponser_user_name);

        if ($sponser_user_id > 0) {
            $this->db->select("COUNT(*) AS cnt");
            $this->db->from("user_details");
            $this->db->where('user_detail_refid', $sponser_user_id);
            $this->db->where('user_detail_name', $sponser_full_name);
            $qr = $this->db->get();
            foreach ($qr->result() as $row) {
                $count = $row->cnt;
            }

            if ($count > 0) {
                $flag = true;
            }
        }
        return $flag;
    }

    public function checkLeg($sponserleg, $sponser_user_name) {
        require_once 'validation.php';
        $obj_val = new Validation();
        $sponserleg = mysql_real_escape_string($sponserleg);
        $sponser_user_name = mysql_real_escape_string($sponser_user_name);
        $sponserid = $obj_val->userNameToID($sponser_user_name);
        return $obj_val->isLegAvailable($sponserid, $sponserleg);
    }

    public function checkUser($user_name) {
        $flag = TRUE;
        if ($user_name == "") {
            $flag = FALSE;
            return $flag;
        }
        $this->db->select("COUNT(*) AS cnt");
        $this->db->from("ft_individual");
        $this->db->where('user_name', $user_name);
        $qr = $this->db->get();

        foreach ($qr->result() as $row) {
            $count = $row->cnt;
        }
        if ($count > 0) {
            $flag = FALSE;
        }
        return $flag;
    }

    public function confirmRegister($regr, $module_status) {


        $this->load->model('registersubmit');
        $this->load->model('configuration_model');

        $reg = new registersubmit();
        $max_nod_id = $reg->getMaxOrderID();
        $next_order_id = $max_nod_id + 1;

        if ($regr['user_name_type'] == 'dynamic') {
            $regr['username'] = $reg->getUsername();
        } else {
            $regr['username'] = $regr['user_name_entry'];
        }
        $regr['fatherid'] = $reg->obj_vali->userNameToID($regr['fatherid']);
        $regr['referral_id'] = $reg->obj_vali->userNameToID($regr['referral_name']);
//        if ($regr['state'] != "") {
//
//            $regr['state'] = $reg->getStateName($this->input->post('state'));
//        }

        if ($this->validateRegisterData($regr, $module_status)) {

            $child_node = $reg->obj_vali->getChildNodeId($regr['fatherid'], $regr['position']);
            $updt_login_res = $res_login_update = $reg->updateLoginUser($regr['username'], md5($regr['pswd']), $child_node);
            if ($res_login_update) {
                $user_level = $reg->getLevel($regr['fatherid']) + 1;
                $updt_ft_res = $res_ftindi_update = $reg->updateFTIndividual($regr['fatherid'], $regr['position'], $regr['username'], $child_node, $next_order_id, $regr['by_using'], $user_level, $regr['prodcut_id']);
                if ($res_ftindi_update) {

                    $last_insert_id = $reg->obj_vali->userNameToID($regr['username']);
                    $pin_status = $module_status['pin_status'];
                    $pin_status;

                    if ($pin_status == "yes") {
                        // $updt_pin_status_res = $pin_upd_res = $reg->updatePinNumber($regr['passcode'], $regr['username']);
                    }
                    $regr['userid'] = $last_insert_id;
                    $res = $reg->insertUserDetails($regr);
                    $updt_ft_uni = $reg->insertToUnilevelTree($regr);
                    
                    $width_ceiling = $this->getWidthCieling();
                    
                    
                    for($i=0;$i<$width_ceiling;$i++){
                        $res1 = $reg->tmpInsert($last_insert_id, $i+1);
                    }
                    
                    $mlm_plan = $module_status['mlm_plan'];

                    if ($mlm_plan == "Matrix") {

                        if (!$reg->isUserLevelFull($regr['fatherid'], $width_ceiling)) {

                            $new_position = $reg->getNewPositionOfUser($last_insert_id) + 1;

                            $res1 = $reg->tmpInsert($regr['fatherid'], $new_position);
                        }
                    } else if ($mlm_plan == "Unilevel" || $mlm_plan == "Party") {

                        $new_position = $reg->getNewPositionOfUser($last_insert_id) + 1;

                        $res1 = $reg->tmpInsert($regr['fatherid'], $new_position);
                    }
                 
                }
            }

            $balance_amount = 0;
            if ($res) {
                $rank_status = $module_status['rank_status'];
                if ($rank_status == "yes") {

                    $referal_count = count($reg->obj_config->getAlldownlineUsers($regr['referral_id']));
                    //$referal_count = $reg->obj_vali->getReferalCount($regr['referral_id']);
                    $old_rank = $reg->obj_vali->getUserRank($regr['referral_id']);
                    $regr['rank'] = $reg->obj_vali->getCurrentRankFromRankConfig($referal_count);
                    $new_rank = $regr['rank'];
                    $this->updateUserRank($regr['referral_id'], $new_rank);
                    if ($old_rank != $new_rank) {

                        $balance_amount = $this->balnceAmount($regr['referral_id']);
                        $rank_bonuss = array();
                        $rank_bonuss = $this->configuration_model->getAllRankDetails($new_rank);
                        $balance_amount = $balance_amount + $rank_bonuss[0]['rank_bonus'];
                        $this->updateUsedEwallet($regr['referral_id'], $balance_amount, "yes");
                        $this->insertIntoRankHistory($old_rank, $regr['rank'], $regr['referral_id']);
                    }
                }
                $referral_status = $module_status['referal_status'];
                if ($referral_status == "yes") {
                    $referal_amount = $this->getReferalAmount();
                    if ($referal_amount > 0) {
                        $referal_amount = $referal_amount + $balance_amount;
                        $ref = $this->obj_calc->insertReferalAmount($regr['referral_id'], $referal_amount, $regr['userid']);
                    }
                }
                $product_status = $module_status['product_status'];
                $product_amount = "";

                if ($product_status == "yes") {

                    $product_amount = $this->obj_product->getProductAmount($regr['prodcut_id']);

                    $this->obj_calc->calculateLegCount($regr['fatherid'], $regr['referral_id'], $product_amount, $regr['userid']);
                } else {

                    $this->obj_calc->calculateLegCount($regr['fatherid'], $regr['referral_id'], $product_amount, $regr['userid']);
                }
                // $mobile = $regr['mobile'];
                $username = $regr['username'];
                $full_name = $regr['full_name'];
                $password = $regr['pswd'];

                $site_info = $this->obj_config->getSiteConfiguration();
                $site_name = $site_info['co_name'];
                $site_logo = $site_info['logo'];
                $base_url = base_url();

                $tran_code = $reg->getRandTransPasscode(8);
                $sponsor = $regr['sponsor'];
                //$trans_id = $regr['trans_id'];

                $reg->savePassCodes($last_insert_id, $tran_code);

                if (($regr['email'] != "") && ($regr['email'] != null)) {
                    $reg_mail = $this->checkMailStatus();
                    if ($reg_mail['reg_mail_status'] == 'yes') {
                        $email = $regr['email']; //echo $email;die();

                        $mail_content = $this->obj_vali->getMailBody();

                        $subject = "Ihre Anmeldung";

                        $mailBodyDetails = '<html xmlns="http://www.w3.org/1999/xhtml">
			                                                <head>
			                                                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			                                          
			                                                    <link href="http://fonts.googleapis.com/css?family=Droid+Serif" rel="stylesheet" type="text/css">
			                                                    <style>
			                                                        html, body {
			                                                            margin:0px;
			                                                            padding:0px;
			                                                        }

			                                                        /*.link a {
			                                                            border:1px solid transparent;
			                                                            color:#ffffff;
			                                                            background-color:#007AFF;
			                                                            display:block;
			                                                            width:50%;
			                                                            margin:0 auto;
			                                                        }

			                                                        .link a:hover {
			                                                            background-color:#ffffff;
			                                                            border-color:#000000;
			                                                            color:#007AFF;
			                                                        }*/

			                                                          
			                                                    </style>

			                                                </head>

			                                                <body>
			                                                    <table width="600" style="padding:40px; margin:50px auto;">
			                                                        <tr>
			                                                            <td colspan="2" width="600" style="margin: 15px 0 0 0;">

			                                                                <!--<div style="width:100%;height:62px;background:url(' . $base_url . 'public_html/images/head-bg.png) no-repeat center center;padding:3px 5px 3px 5px;">
			                                                                    <h1>Mighty-Buyer</h1>
			                                                                </div>-->

			                                                                <img src="' . $base_url . 'public_html/images/mail_image.jpg" width="600">
			                                                            </td>
			                                                        </tr>

			                                                        <tr>
			                                                            <td colspan="2" style="padding-top:20px;">

			                                                                <h3 style="font: normal 20px Tahoma, Geneva, sans-serif;">Herzlich Willkommen <font color="#007AFF">' . $full_name . ',</font></h3><br>
			                                                                <p style="font: normal 12px Tahoma, Geneva, sans-serif;text-align:justify;color:#212121;line-height:20px;">wir begl&uuml;ckw&uuml;nschen Dich zu Deinem Entschluss mit Mighty Buyer Dein eigenes Gesch&auml;ft aufbauen zu wollen! Das ganze Team von Mighty Buyer wird Dich bestm&ouml;glich darin unterst&uuml;tzen Dir hier eine erfolgreiche Zukunft aufbauen zu k&ouml;nnen - und das bereits innerhalb weniger Monate!</p>
			                                                                <p style="font: normal 12px Tahoma, Geneva, sans-serif;text-align:justify;color:#212121;line-height:20px;padding-bottom: 13px;">Zun&auml;chst notiere Dir bitte Deine Zugangsdaten zu Deinem pers&ouml;nlichen Online-B&uuml;ro. Diese brauchst du f&uuml;r Deinen Login</p>
			                                                                <p style="font: normal 12px Tahoma, Geneva, sans-serif;text-align:justify;color:#212121;line-height:20px;padding-bottom: 13px; font-weight:bold;">
			                                                                Benutzername: ' . $username . '<br>
			                                                                Passwort: ' . $password . '<br>
			                                                                Transaction ID: ' . $tran_code . '<br>
			                                                                Sponsor: ' . $sponsor . '<br>
			                                                                </p>
																			<p style="font: normal 12px Tahoma, Geneva, sans-serif;text-align:justify;color:#212121;line-height:20px;padding-bottom: 13px;">
																			Verwahre diese Daten bitte an einem sicheren Ort, damit niemand au&szlig;er Dir Zugang zu Deinem pers&ouml;nlichen Online-B&uuml;ro erh&auml;lt.<br>
																			Dein Online-B&uuml;ro ist praktisch deine Firmenzentrale. Hier erh&auml;ltst Du n&uuml;tzliche Informationen, erh&auml;ltst eine Vielzahl von Statistiken, wie z.B. die Anzahl Deiner Shop-Besucher, Ums&auml;tze und Deine Provisionen.<br>
																			Hier kannst Du auch neue Partner einladen, um Dein Team zu vergr&ouml;&szlig;ern und vieles mehr.
			                                                                </p>
																			<p style="font: normal 12px Tahoma, Geneva, sans-serif;text-align:justify;color:#212121;line-height:20px;padding-bottom: 13px;">
																			Am besten Du loggst Dich direkt ein und verschaffst Dir selbst einen kleinen &Uuml;berblick.<br>
																			Nach dem Login findest Du in der linken Leiste den Men&uuml;punkt "Tools" -> "Info". Hier findest Du eine praktische Anleitung zu Deinem Online-B&uuml;ro und weitere hilfreiche Tutorials, wie z.B. Teamaufbau und Verdienst.
			                                                                </p>
			                                                                <p style="line-height:23px; text-align:center; ">
			                                                                    <a style="font: normal 14px Tahoma, Geneva, sans-serif;text-decoration:none;background-color: #007AFF;padding: 5px 10px;display: block;color:#FFFFFF;" href=' . "$base_url" . '>Jetzt Registrierung abschlie&szlig;en</a>
			                                                                 </p>			                                                                
			                                                            </td>
			                                                        </tr>
			                                                        <tr>
			                                                            <td width="400" height="100" style="height:100px;">
			                                                                <p><br></p>
			                                                                <p style="font: normal 12px Tahoma, Geneva, sans-serif;">Wir freuen uns auf eine erfolgreiche, gemeinsame Zukunft mit Dir!</p>
			                                                                <p><br></p>
			                                                                <p style="font: normal 12px Tahoma, Geneva, sans-serif;">Viele Gr&uuml;&szlig;e,<br>
			                                                                Dein <b>Mighty Buyer</b> Team!</p>
			                                                            </td>
			                                                            <td width="200" text-align="right">
			                                                                <img src="' . $base_url . 'public_html/images/logos/mightylogo_10122014z.png" alt="Mighty Buyer" width="200" />
			                                                            </td>
			                                                        </tr>			                                                        
			                                                    </table>
			                                                </body>
			                                            </html>';

                        /*
                          $mailBodyDetails = '<html xmlns="http://www.w3.org/1999/xhtml">
                          <head>
                          <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

                          <link href="http://fonts.googleapis.com/css?family=Droid+Serif" rel="stylesheet" type="text/css">
                          <style>

                          margin:0px;
                          padding:0px;

                          </style>

                          </head>

                          <body>
                          <div style="width:80%;padding:40px;border: solid 10px #D0D0D0;margin:50px auto;">
                          <div style="lwidth:100%;height:62px;border: solid 1px #D0D0D0;background:url(' . $base_url . 'public_html/images/head-bg.png) no-repeat center center;padding:3px 5px 3px 5px;">
                          <img src="' . $base_url . 'public_html/images/logos/' . $site_logo . '" alt="logo" />
                          </div>
                          <div style="width:100%;margin:15px 0 0 0;">
                          <h1 style="font: normal 20px Tahoma, Geneva, sans-serif;">Dear <font color="#e10000">' . $full_name . ' doe name,</font></h1><br>
                          <p style="font: normal 12px Tahoma, Geneva, sans-serif;text-align:justify;color:#212121;line-height:23px;">' . $mail_content . '</p>
                          <div style="width:400px;height:225px;margin:16px auto;background:url' . $base_url . 'public_html/images/page.png);border: solid 1px #d0d0d0;border-radius: 10px;">
                          <img src="' . $base_url . 'public_html/images/login-icons.png" width="35px" height="35px" style="float:left;margin-top:10px;margin-left:10px;"/><h2 style="color:#C70716;font:normal 16px Tahoma, Geneva, sans-serif;line-height:34px;margin:10px 0 0 22px;float:left;padding-left: 0px;">LOGIN DETAILS</h2>
                          <div style="clear:both;"></div>
                          <ul style="display:block;margin:14px 0 0 -36px;float:left;">
                          <li style="list-style:none;font:normal 15px Tahoma, Geneva, sans-serif;color:#212121;margin:5px 0 0 20px;border:1px solid #ccc;background:#fff;width:300px;padding:5px;"><span style="width:150px;float:left;"> Login Link</span><font color="#025BB9"> : <a href=' . "$base_url" . '>Click Here</a></font></li>

                          <li style="list-style:none;font:normal 15px Tahoma, Geneva, sans-serif;color:#212121;margin:5px 0 0 20px;border:1px solid #ccc;background:#fff;width:300px;padding:5px;"><span style="width:150px;float:left;">Your UserName</span><font color="#e10000"> : ' . $username . '</font></li>
                          <li style="list-style:none;font:normal 15px Tahoma, Geneva, sans-serif;color:#212121;margin:5px 0 0 20px;border:1px solid #ccc;background:#fff;width:300px;padding:5px;"><span style="width:150px;float:left;">Your Password</span><font color="#e10000"> : ' . $password . '</font></li>
                          </ul>
                          </div>
                          <p><br /><br /><br /><br /> </p>
                          </div>

                          </div>
                          </body>
                          </html>'; */

                        $send_mail = $this->sendEmail($mailBodyDetails, $email, $reg_mail);
                        //$send_mail = $this->obj_vali->sendEmail($mailBodyDetails, $email, $subject);
                    }
                }

                $reg->insertBalanceAmount($regr['userid']);
//                $encr_id = $this->getEncrypt($this->session->userdata['logged_in']['user_id']);
                $encr_id = $regr['userid'];
                $msg['user'] = $username;
                $msg['pwd'] = $password;
                $msg['id'] = $encr_id;
                $msg['status'] = true;
                $msg['tran'] = $tran_code;

                return $msg;
            } else {

                $msg['status'] = false;

                return $msg;
            }
        }
    }

    function getEncrypt($string) {

        $key = "EASY1055MLM!@#$";
        $result = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result.=$char;
        }
        return base64_encode($result);
    }

    public function insertIntoRankHistory($old_rank, $new_rank, $ref_id) {

        $date = date('Y-m-d H:i:s');
        $this->db->set('user_id', $ref_id);
        $this->db->set('current_rank', $old_rank);
        $this->db->set('new_rank', $new_rank);
        $this->db->set('date', $date);
        $res = $this->db->insert('rank_history');
        return $res;
    }

    public function updateUserRank($id, $rank) {


        $this->db->set('user_rank_id', $rank);
        $this->db->where('id', $id);
        $result = $this->db->update('ft_individual');
    }

    public function getReferalAmount() {


        $this->db->select('referal_amount');
        $this->db->from('configuration');
        $query = $this->db->get();
        foreach ($query->result() as $row) {
            $referal_amount = $row->referal_amount;
        }

        return $referal_amount;
    }

    public function isUserAvailable($user_name) {

        $this->db->select("COUNT(*) AS cnt");
        $this->db->from("ft_individual");
        $this->db->where('user_name', $user_name);
        $this->db->where('active !=', 'server');


        $qr = $this->db->get();
        foreach ($qr->result() as $row) {
            $cnt = $row->cnt;
        }

        if ($cnt > 0) {
            $flag = true;
        } else {
            $flag = false;
        }
        return $flag;
    }

    public function getTermsConditions($lang_id = '') {

        $this->db->select('terms_conditions');
        $this->db->from("terms_conditions");
        if ($lang_id != '')
            $this->db->where("lang_ref_id", $lang_id);
        $query = $this->db->get();
        foreach ($query->result() as $row) {

            $terms_con = $row->terms_conditions;
        }
        return stripslashes($terms_con);
    }

    public function viewCountry($lang_id = "") {

        $country_detail = "";

        $this->db->select('*');
        $this->db->from('country_all');
        //$this->db->where("lang_ref_id", 1);
        $this->db->order_by('country_name', 'ASC');
        $query = $this->db->get();
        $i = 0;
        foreach ($query->result_array() as $row) {
            $country_detail .= "<option value='" . $row['country_id'] . "'>" . $row['country_name'] . "</option>";
        }

        return $country_detail;
    }

    public function getLetterSetting($id) {

        $arr = "";

        $this->db->select('*');
        $this->db->from('letter_config');
        $this->db->where('id', $id);
        $query = $this->db->get();
        //$query = $this->db->get('letter_config');
        foreach ($query->result() as $row) {

            $arr['company_name'] = $row->company_name;
            $arr['address_of_company'] = $row->address_of_company;
            $arr['main_matter'] = $row->main_matter;
            $arr['logo'] = $row->logo;
            $arr['productmatter'] = $row->productmatter;
            $arr['place'] = $row->place;
        }
        return $arr;
    }

    public function getUidFromUsername($uname) {

        $user_id = "";
        $this->db->select('user_id');
        $this->db->from('login_user');
        $this->db->where('user_name', $uname);
        $query = $this->db->get();
        foreach ($query->result() as $row) {
            $user_id = $row->user_id;
        }
        return $user_id;
    }

    public function getUserDetails($uid) {

        $this->db->select('*');
        $this->db->from('user_details');
        $this->db->where('user_detail_refid', $uid);
        $query = $this->db->get();
        foreach ($query->result() as $row) {
            $arr = $row;
        }
        return $arr;
    }

    public function getFatherName($uid) {

        $this->db->select('*');
        $this->db->from('ft_individual');
        $this->db->where('id', $uid);
        $query = $this->db->get();
        foreach ($query->result() as $row) {
            return $row;
        }
    }

    public function getFname($fid) {
        $fnam = "";

        $this->db->select('user_name');
        $this->db->from('login_user');
        $this->db->where('user_id', $fid);
        $query = $this->db->get();

        foreach ($query->result() as $row) {
            $fnam = $row->user_name;
        }

        return $fnam;
    }

    public function getProduct($prdtid) {

        $this->db->select('*');
        $this->db->from('product');
        $this->db->where('product_id', $prdtid);
        $query = $this->db->get();
        foreach ($query->result() as $row) {
            return $row;
        }
    }

    public function getReferralName($user_id) {


        $user_detail_name = "";

        $this->db->select('*');
        $this->db->from('user_details');
        $this->db->join('invite_partner_details','user_details.user_detail_refid = invite_partner_details.user_id');    
        $this->db->where('user_detail_refid', $user_id);
        $query = $this->db->get();
        
        foreach ($query->result() as $row) {
            $data['invite_full_name'] =  $row->invite_full_name;
            $data['invite_nick_name'] =  $row->invite_nick_name;
            $data['invite_mobile_number'] =  $row->invite_mobile_number;
            $data['user_detail_name'] =  $row->user_detail_name;
            $data['user_detail_nickname'] =  $row->user_detail_nickname;
            $data['user_detail_mobile'] =  $row->user_detail_mobile;
        }
        return $data;
    }
    
    
    public function getFirstName($user_id){
        $user_detail_nickname = "";

        $this->db->select('user_detail_nickname');
        $this->db->from('user_details');
        $this->db->where('user_detail_refid', $user_id);
        $query = $this->db->get();

        foreach ($query->result() as $row) {
            $user_detail_nickname = $row->user_detail_nickname;
        }

        return $user_detail_nickname;
        
    }

    


    public function sendEmail($mailBodyDetails, $email, $reg_mail) {
        $this->mailObj->From = $this->getCompanyEmail();
        $this->mailObj->FromName = $reg_mail['from_name'];
        $this->mailObj->Subject = " Ihre Anmeldung";
        $this->mailObj->IsHTML(true);
        $this->mailObj->ClearAddresses();
        $this->mailObj->AddAddress($email);
        $this->mailObj->Body = $mailBodyDetails;
        $res = $this->mailObj->send();
        $arr["send_mail"] = $res;
        $date = date("Y-m-d h:i:sa");
        if (!$res) {
            $arr['error_info'] = $this->mailObj->ErrorInfo;

            $this->db->set('from_id', $this->getCompanyEmail());
            $this->db->set('to_id', $email);
            $this->db->set('body_details', $mailBodyDetails);
            $this->db->set('date', $date);
            $this->db->set('status', $this->mailObj->ErrorInfo);
            $result = $this->db->insert("mail_history");
        } else {
            $this->db->set('from_id', $this->getCompanyEmail());
            $this->db->set('to_id', $email);
            $this->db->set('body_details', $mailBodyDetails);
            $this->db->set('date', $date);
            $this->db->set('status', "yes");
            $result = $this->db->insert("mail_history");
        }
        return $arr;
    }

    public function getCompanyEmail() {
        $email = "";
        $this->db->select('email');
        $this->db->from('site_information');
        $this->db->where('id', 1);
        $res = $this->db->get();
        foreach ($res->result() as $row) {
            $email = $row->email;
        }
        return $email;
    }

    public function getWidthCieling() {

        $obj_arr = $this->getSettings();
        $width_cieling = $obj_arr["width_cieling"];
        return $width_cieling;
    }

    public function getSettings() {
        $obj_arr = array();
        $this->db->select("*");
        $this->db->from("configuration");
        $res = $this->db->get();
        foreach ($res->result_array() as $row) {
            $obj_arr["id"] = $row['id'];
            $obj_arr["tds"] = $row['tds'];
            $obj_arr["percentorvalue"] = $row['percentorvalue'];
            $obj_arr["service_charge"] = $row['service_charge'];
            $obj_arr["width_cieling"] = $row['width_cieling'];
            $obj_arr["depth_cieling"] = $row['depth_cieling'];
            $obj_arr["startDate"] = $row['start_date'];
            $obj_arr["endDate"] = $row['end_date'];
            $obj_arr["sms_status"] = $row['sms_status'];
            $obj_arr["payout_release"] = $row['payout_release'];
            $obj_arr["reg_amount"] = $row['reg_amount'];
            $obj_arr["referal_amount"] = $row['referal_amount'];
        }

        return $obj_arr;
    }

    public function isLegAvailable($sponserid, $sponserleg, $module_status) {
        $flag = 0;

        $mlm_plan = $module_status['mlm_plan'];

        if ($mlm_plan == "Matrix") {

            $width_cieling = $this->getWidthCieling();
        }


        $this->db->select("COUNT(*) AS cnt");
        $this->db->from("ft_individual");
        $this->db->where('father_id', $sponserid);
        $this->db->where('position', $sponserleg);
        $this->db->where('active', 'yes');
        $qr = $this->db->get();
        foreach ($qr->result() as $row) {
            $count = $row->cnt;
        }




        if ($sponserid == "") {

            $flag = 0;
        } if ($mlm_plan == "Matrix") {
            if ($count > $width_cieling) {
                $flag = 0;
            }
        } else if ($count > 0) {
            $flag = 0;
        }

        $sponser = $this->IdToUserName($sponserid);
        $user = $this->isUserAvailable($sponser);
        if (!$user) {
            $flag = 0;
        } else {
            $flag = 1;
        }


        return $flag;
    }

    public function isUserNameAvailable($user_name) {


        $flag = 0;

        $this->db->select("COUNT(*) AS cnt");
        $this->db->from("ft_individual");
        $this->db->where('user_name', $user_name);
        $this->db->where('active !=', 'server');
        $qr = $this->db->get();
        foreach ($qr->result() as $row) {
            $count = $row->cnt;
        }

        if ($count > 0) {
            $flag = 1;
        }

        return $flag;
    }

    public function IdToUserName($user_id) {

        $user_name = "";

        $this->db->select('user_name');
        $this->db->from('ft_individual');
        $this->db->where('id', $user_id);
        $query = $this->db->get();
        foreach ($query->result() as $row) {
            $user_name = $row->user_name;
        }

        return $user_name;
    }

    public function countryNameFromID($country_id) {
        $this->db->select('country_name');
        $this->db->from('country_all');
        $this->db->where('country_id', $country_id);
        $res = $this->db->get();
        foreach ($res->result() as $row)
            return $row->country_name;
    }

    public function checkMailStatus() {
        $stat = "";
        $this->db->select('from_name');
        $this->db->select('reg_mail_status');
        $this->db->from('mail_settings');
        $this->db->where('id', 1);
        $res = $this->db->get();
        foreach ($res->result_array() as $row) {
            $stat = $row;
        }
        return $stat;
    }

    public function insertIntoSalesOrder($user_id, $prod_id, $payment_method = "") {

        //$random_numbers = range(0, 999);
        $last_inserted_id = $this->db->insert_id();
        $invoice_no = 1000 + $last_inserted_id;


        $this->db->select('product_value');
        $this->db->from('product');
        $this->db->where('product_id', $prod_id);
        $query = $this->db->get();
        foreach ($query->result() as $row) {
            $amount = $row->product_value;
        }

        $date = date('Y-m-d H:i:s');
        $this->db->set('invoice_no', $invoice_no);
        $this->db->set('prod_id', $prod_id);
        $this->db->set('user_id', $user_id);
        $this->db->set('amount', $amount);
        $this->db->set('date_submission', $date);
        $this->db->set('payment_method', $payment_method);
        $res = $this->db->insert('sales_order');

        return $res;
    }

    public function insertUserActivity($login_id, $activity, $done_by) {

        $date = date("Y-m-d H:i:s");
        $ip_adress = $_SERVER['REMOTE_ADDR'];
        $this->db->set('user_id', $login_id);
        $this->db->set('activity', $activity);
        $this->db->set('done_by', $done_by);
        $this->db->set('ip', $ip_adress);
        $this->db->set('date', $date);
        $result = $this->db->insert('activity_history');
    }

    //------------------------------
    public function insertIntoLeg($user, $fatherid) {
        $this->db->set('from_id', $user);
        $this->db->where('user_id', $fatherid);
        $result = $this->db->update('leg_amount');
        return $result;
    }

    public function getEpin($epin) {
        $epin_arr = array();
        $date = date('Y-m-d H:m:s');
        $this->db->select("*");
        $this->db->from('pin_numbers');
        $this->db->where('pin_numbers', $epin);
        $this->db->where('status', "yes");
        //$this->db->where('allocated_user_id', "NA");
        $this->db->where("pin_expiry_date >=", $date);
        $res = $this->db->get();
        foreach ($res->result_array() as $row) {
            $epin_arr["pin_numbers"] = $row['pin_numbers'];
            $epin_arr["pin_amount"] = $row['pin_balance_amount'];
        }

        return $epin_arr;
    }

    // ---------------
    public function getCountryTelephoneCode($country_id) {
        $this->db->select('phone_code')->where('country_id', $country_id)->limit(1)->from('country_all');
        $res = $this->db->get();
        foreach ($res->result() as $row) {
            return $row->phone_code;
        }
    }

    public function insertCredicardDetails($credit_card) {
        $data = array(
            'ecom_user_id' => $credit_card['user_id'],
            'credit_card_number' => $credit_card['card_no'],
            'credit_card_type' => $credit_card['credit_card_type'],
            'credit_date' => $credit_card['card_expiry_date'],
            'credit_invoice_number' => $credit_card['card_veri_no'],
            'credit_user_forename' => $credit_card['card_forename'],
            'credit_user_surname' => $credit_card['card_surename'],
            'credit_email' => $credit_card['card_email'],
            'mobile_no' => $credit_card['card_phone'],
        );

        $res = $this->db->insert('credit_card_purchase_details', $data);
        return $res;
    }

    public function UpdateUsedEpin($pin_det, $arr_length) {
        //$pin_count = count($pin_det);
        $user_id = $this->obj_vali->userNameToID($pin_det['user']);
        $date = date('Y-m-d H:m:s');

        for ($i = 0; $i < $arr_length; $i++) {
            $pin_no = $pin_det[$i . 'pin'];

            $pin_balnce = $pin_det[$i . 'bal_amount'];


            if ($pin_balnce == 0)
                $this->db->set('status', 'no');
            else
                $this->db->set('status', 'yes');


            $this->db->set('pin_alloc_date', $date);
            $this->db->set('used_user', $user_id);
            $this->db->set('pin_balance_amount', $pin_balnce);
            $this->db->where('pin_numbers', $pin_no);
            $result = $this->db->update('pin_numbers');
        }

        return $result;
    }

    public function insertUsedPin($epin_det, $arr_length) {

        $user_id = $this->obj_vali->userNameToID($epin_det['user']);
        $date = date('Y-m-d H:m:s');

        for ($i = 0; $i < $arr_length; $i++) {
            $pin_no = $epin_det[$i . 'pin'];
            $pin_balnce = $epin_det[$i . 'bal_amount'];
            $pin_amount = $epin_det[$i . 'pin_amount'];

            if ($pin_balnce == 0)
                $this->db->set('status', 'no');
            else
                $this->db->set('status', 'yes');
            $this->db->set('pin_number', $pin_no);
            $this->db->set('used_user', $user_id);
            $this->db->set('pin_alloc_date', $date);
            $this->db->set('pin_amount', $pin_amount);
            $this->db->set('pin_balance_amount', $pin_balnce);

            $res = $this->db->insert('pin_used');
        }

        return $res;
    }

    public function balnceAmount($user_id, $balance = '') {
        $user_balance = "";
        $this->db->select('balance_amount');
        $this->db->select('user_id');
        $this->db->where('user_id', $user_id);
        if ($balance != '')
            $this->db->where('balance_amount >', $balance);
        $this->db->from('user_balance_amount');
        $res = $this->db->get();

        foreach ($res->result_array() as $row) {

            $user_balance = $row['balance_amount'];
        }
        return $user_balance;
    }

    public function ewalletPassword($user_id, $password) {
        $flag = "no";

        $this->db->select("COUNT(*) AS cnt");
        $this->db->where('user_id', $user_id);
        $this->db->where('tran_password', $password);
        $this->db->from('tran_password');
        $qr = $this->db->get();

        foreach ($qr->result() as $row) {
            $count = $row->cnt;
        }

        if ($count > 0)
            $flag = "yes";

        return $flag;
    }

    public function insertUsedEwallet($ref_user, $user, $used_amount) {
        $date = date('Y-m-d H:i:s');
        $user_id = $this->obj_vali->userNameToID($user);
        $user_ref_id = $this->obj_vali->userNameToID($ref_user);
        $this->db->set('used_user_id', $user_ref_id);
        $this->db->set('used_amount', $used_amount);
        $this->db->set('user_id', $user_id);
        $this->db->set('used_for', 'registration');
        $this->db->set('date', $date);
        $res1 = $this->db->insert('live_account_registration_details');
        return $res1;
    }

    public function updateUsedEwallet($ewallet_user, $amount, $up_bal = '') {
        if ($up_bal == '') {
            $user_id = $this->obj_vali->userNameToID($ewallet_user);
        } else {
            $user_id = $this->obj_vali->userNameToID($ewallet_user);
        }
        $this->db->set('balance_amount', 'balance_amount - ' . $amount, FALSE);
        $this->db->where('user_id', $user_id);
        $result = $this->db->update('user_balance_amount');
        return $result;
    }

    public function getprdctAmount($p_id) {


        $prct_amount = $this->obj_product->getProductAmount($p_id);
        return $prct_amount;
    }

    public function inserintoPaymenDetails($payment_details) {

        $data = array(
            'type' => $payment_details['payment_method'],
            'user_id' => $payment_details['user_id'],
            'acceptance' => $payment_details['acceptance'],
            'payer_id' => $payment_details['payer_id'],
            'order_id' => $payment_details['token_id'],
            'amount' => $payment_details['amount'],
            'currency' => $payment_details['currency'],
            'status' => $payment_details['status'],
            'card_number' => $payment_details['card_number'],
            'ED' => $payment_details['ED'],
            'card_holder_name' => $payment_details['card_holder_name'],
            'date_of_submission' => $payment_details['submit_date'],
            'pay_id' => $payment_details['pay_id'],
            'error_status' => $payment_details['error_status'],
            'brand' => $payment_details['brand']
        );
        $res = $this->db->insert('payment_registration_details', $data);
        return $res;
    }

    public function getPaymentGatewayStatus() {

        $details ['paypal_status'] = $this->getGatewayStatus('Paypal');
        $details ['creditcard_status'] = $this->getGatewayStatus('Creditcard');
        $details ['epdq_status'] = $this->getGatewayStatus('EPDQ');
        $details ['authorize_status'] = $this->getGatewayStatus('Authorize.net');

        return $details;
    }

    public function getGatewayStatus($gateway) {
        $this->db->select('status');
        $this->db->like('gateway_name', $gateway);
        $this->db->from("payment_gateway_config");
        $this->db->limit(1);
        $query = $this->db->get();

        foreach ($query->result() as $row) {

            return $row->status;
        }
    }

    public function getPaymentModuleStatus() {

        $details = array();
        $details ['gateway_type'] = $this->getPaymentStatus('Credit Card'); //changed 8/11/2014 Payment Gateway
        $details ['epin_type'] = $this->getPaymentStatus('E-pin');
        $details ['free_joining_type'] = $this->getPaymentStatus('Free Joining');
        $details ['ewallet_type'] = $this->getPaymentStatus('E-wallet');
        return $details;
    }

    public function getPaymentStatus($type) {
        $this->db->select('status');
        $this->db->like('payment_type', $type);
        $this->db->from("payment_methods");
        $this->db->limit(1);
        $query = $this->db->get();

        foreach ($query->result() as $row) {

            return $row->status;
        }
    }

    public function getRegisterAmount() {

        $this->db->select('reg_amount');
        $this->db->from('configuration');
        $res = $this->db->get();
        foreach ($res->result() as $row) {

            $amount = $row->reg_amount;
        }

        return $amount;
    }

    public function getPrdocut($p_id) {

        return $this->obj_product->getPrdocutName($p_id);
    }

    public function getBalanceAmount($user_id) {
        $this->db->select('balance_amount');
        $this->db->from('user_balance_amount');
        $this->db->where('user_id', $user_id);
        $res = $this->db->get();
        foreach ($res->result() as $row) {

            $amount = $row->balance_amount;
        }

        return $amount;
    }

    public function updateBalanceAmount($balance_amount, $user_id) {
        $this->db->set('balance_amount', $balance_amount);
        $this->db->where('user_id', $user_id);
        $result = $this->db->update('user_balance_amount');
        return $result;
    }

    public function generateOrderid($name, $type) {
        $order_id = null;
        $date = date('Y-m-d H:i:s');
        $this->db->set('firstname', $name);
        $this->db->set('status', $type);
        $this->db->set('date_added', $date);
        $res = $this->db->insert('epdq_payment_order');
        $order_id = $this->db->insert_id();
        return $order_id;
    }

    public function insertintoPaymenDetails($payment_details) {

        $data = array(
            'type' => $payment_details['payment_method'],
            'user_id' => $payment_details['user_id'],
            'acceptance' => $payment_details['acceptance'],
            'payer_id' => $payment_details['payer_id'],
            'order_id' => $payment_details['token_id'],
            'amount' => $payment_details['amount'],
            'currency' => $payment_details['currency'],
            'status' => $payment_details['status'],
            'card_number' => $payment_details['card_number'],
            'ED' => $payment_details['ED'],
            'card_holder_name' => $payment_details['card_holder_name'],
            'date_of_submission' => $payment_details['submit_date'],
            'pay_id' => $payment_details['pay_id'],
            'error_status' => $payment_details['error_status'],
            'brand' => $payment_details['brand']
        );
        $res = $this->db->insert('payment_registration_details', $data);
        return $res;
    }

    public function authorizePay($api_login_id, $transaction_key, $amount, $fp_sequence, $fp_timestamp) {
        require_once 'anet_php_sdk/AuthorizeNet.php';
        $fingerprint = AuthorizeNetSIM_Form::getFingerprint($api_login_id, $transaction_key, $amount, $fp_sequence, $fp_timestamp);
        return $fingerprint;
    }

    public function insertAuthorizeNetPayment($response, $user_id) {

        $date = date('Y-m-d H:i:s');
        $this->db->set('user_id', $user_id);
        $this->db->set('first_name', $response['x_first_name']);
        $this->db->set('last_name', $response['x_last_name']);
        $this->db->set('company', $response['x_company']);
        $this->db->set('address', $response['x_address']);
        $this->db->set('city', $response['x_city']);
        $this->db->set('state', $response['x_state']);
        $this->db->set('zip', $response['x_zip']);
        $this->db->set('country', $response['x_country']);
        $this->db->set('phone', $response['x_phone']);
        $this->db->set('fax', $response['x_fax']);
        $this->db->set('email', $response['x_email']);
        $this->db->set('date', $date);
        $this->db->set('invoice_num', $response['x_invoice_num']);
        $this->db->set('description', $response['x_description']);
        $this->db->set('cust_id', $response['x_cust_id']);
        $this->db->set('ship_to_first_name', $response['x_ship_to_first_name']);
        $this->db->set('ship_to_last_name', $response['x_ship_to_last_name']);
        $this->db->set('ship_to_company', $response['x_ship_to_company']);
        $this->db->set('ship_to_address', $response['x_ship_to_address']);
        $this->db->set('ship_to_city', $response['x_ship_to_city']);
        $this->db->set('ship_to_state', $response['x_ship_to_state']);
        $this->db->set('ship_to_zip', $response['x_ship_to_zip']);
        $this->db->set('ship_to_country', $response['x_ship_to_country']);
        $this->db->set('amount', $response['x_amount']);
        $this->db->set('tax', $response['x_tax']);
        $this->db->set('duty', $response['x_duty']);
        $this->db->set('freight', $response['x_freight']);
        $this->db->set('auth_code', $response['x_auth_code']);
        $this->db->set('trans_id', $response['x_trans_id']);
        $this->db->set('method', $response['x_method']);
        $this->db->set('card_type', $response['x_card_type']);
        $this->db->set('account_number', $response['x_account_number']);
        $res = $this->db->insert('authorize_payment_details');
        return $res;
    }

    public function getAuthorizeDetails() {
        return $this->obj_config->getAuthorizeConfigDetails();
    }

    public function insertTempRegDetails($regr) {

        $date = date('Y-m-d h:i:s');
        $this->db->set('email', $regr['email']);
        $this->db->set('name', $regr['name']);
        $this->db->set('position', $regr['position']);
        $this->db->set('sponsor_username', $regr['sponsor']);
        $this->db->set('placement', $regr['placement']);
        $this->db->set('date', $date);
        $this->db->set('last_mail_date', $date);
        $query = $this->db->insert('temp_reg_details');
        return $query;
    }

    public function validateEmail($email) {
        $status = 'available';
        $this->db->select('*');
        $this->db->where('email', $email);
        $this->db->not_like('status', 'cancel');
        $res = $this->db->get('temp_reg_details');
        //echo $this->db->last_query();die();
        $row_count = $res->num_rows();
        if ($row_count > 0) {
            $status = 'notavailable';
        } else {
            $count = $this->CheckUserdetailEmail($email);
            if ($count > 0)
                $status = 'notavailable';
        }
        return $status;
    }

    function CheckUserdetailEmail($email) {
        $this->db->select('*');
        $this->db->from('user_details');
        $this->db->where('user_detail_email', $email);
        $qry = $this->db->get();
        $count = $qry->num_rows();
        return $count;
    }

    public function getTempRegId($email) {
        $id = 0;
        $this->db->select('id');
        $this->db->where('email', $email);
        $res = $this->db->get('temp_reg_details');
        foreach ($res->result() as $row)
            $id = $row->id;
        return $id;
    }

    public function getTempRegDetails($reg_id) {
        $arr = array();
        $this->db->select('*');
        $this->db->where('id', $reg_id);
        $this->db->where('status', 'yes');
        $res = $this->db->get('temp_reg_details');
        foreach ($res->result() as $row) {
            $arr['email'] = $row->email;
            $arr['name'] = $row->name;
            $arr['position'] = $row->position;
            $arr['sponsor_username'] = $row->sponsor_username;
            $arr['placement'] = $row->placement;
            $arr['status'] = $row->status;
        }
        return $arr;
    }

    function updateTempRegStatus($email) {
        $this->db->set('status', 'no');
        $this->db->where('email', $email);
        $res = $this->db->update('temp_reg_details');
        return $res;
    }

    function getsponsorFullname($username) {

        $user_id = $this->validation->userNameToID($username);

        $name = $this->validation->getFullName($user_id);

        return $name;
    }

    function getMonthlyFee() {

        $this->db->select('monthly_fee');
        $query = $this->db->get('monthly_fee_config');
        foreach ($query->result() as $row) {
            $amount = $row->monthly_fee;
        }
        return $amount;
    }

    function updateTempRegDetails($serialize_reg, $email) {


        $this->db->set('user_details', $serialize_reg);
        $this->db->where('email', $email);
        $query = $this->db->update('temp_reg_details');

        return $query;
    }

    function getIdFromTempRegDetails($email) {

        $this->db->select('id');
        $this->db->where('email', $email);
        $query = $this->db->get('temp_reg_details');

        foreach ($query->result() as $row) {

            $id = $row->id;
        }

        return $id;
    }

    function getTempUserDetails($temp_id) {

        $this->db->select('user_details');
        $this->db->where('id', $temp_id);
        $query = $this->db->get('temp_reg_details');

        foreach ($query->result_array() as $row) {
            $arr = $row['user_details'];
        }


        return $arr;
    }

    public function saveData($data, $transaction_id, $status) {

        $date = date('Y-m-d h:i:s');
        $this->db->insert('notify_data', array('data' => serialize($data),
            'transaction_id' => $transaction_id,
            'status' => $status,
            'date' => $date));
    }

    public function payment_history($email, $SofortLibTransactionData, $trans_id, $payment_status, $temp_regr, $user_id) {

        $date = date('Y-m-d h:i:s');
        $arr = array('email' => $email,
            'transaction_id' => $trans_id,
            'response' => serialize($SofortLibTransactionData),
            'payment_status' => $payment_status,
            'reg_status' => 'no',
            'user_details' => $temp_regr,
            'date' => $date,
            'user_id' => $user_id);
        $res = $this->db->insert('payment_history', $arr);
    }

    public function getTempRegUserDetails($transaction_id) {

        $this->db->select('user_details');
        $this->db->where('transaction_id', $transaction_id);
        $this->db->where('reg_status', 'no');
        $query = $this->db->get('payment_history');

        foreach ($query->result() as $row) {

            $data = $row->user_details;
        }

        return unserialize($data);
    }

    public function updatePaymentStatus($transaction_id) {

        $this->db->set('reg_status', 'yes');
        $this->db->where('transaction_id', $transaction_id);
        $this->db->update('payment_history');
    }

    public function getEmailId($user_id) {

        $this->db->select('user_detail_email');
        $this->db->where('user_detail_refid', $user_id);
        $query = $this->db->get('user_details');

        foreach ($query->result() as $row) {
            $email = $row->user_detail_email;
        }
        return $email;
    }

    public function updateRegistrationStatus($user_id) {

        $this->db->set('monthly_active_status', 'yes');
        $this->db->where('id', $user_id);
        $this->db->update('ft_individual');
    }

    function getUserId($transaction_id) {

        $this->db->select('user_id');
        $this->db->where('transaction_id', $transaction_id);
        $query = $this->db->get('payment_history');

        foreach ($query->result() as $row) {

            $user_id = $row->user_id;
        }

        return $user_id;
    }

    public function insertMonthlyFeeHistory($user_id, $method, $fee) {

        $date = date('Y-m-d H:i:s');
        $arr = array('user_id' => $user_id,
            'amount' => $fee,
            'method' => $method,
            'date' => $date);
        $res = $this->db->insert('monthly_fee_history', $arr);

        return $res;
    }

    public function insertAmountHistory($user_id, $fee, $status) {

        $date = date('Y-m-d H:i:s');
        $arr = array('user_id' => $user_id,
            'amount' => $fee,
            'amount_type' => $status,
            'date' => $date);

        $res = $this->db->insert('user_deduct_amount', $arr);
        return $res;
    }

    function getCountryNameFromID($country_id) {
        $res = $this->db->get_where('country_all', array('country_id' => $country_id));
        foreach ($res->result() as $row)
            return $row->country_name;
    }

    function emailIsAlreadyRegistred($email) {

        $email = '';
        $this->db->select('user_detail_email');
        $this->db->where('user_detail_email', $email);
        $res = $this->db->get('user_details');

        foreach ($res->result() as $row) {

            $email = $row->user_detail_email;
        }

        if ($email) {
            return TRUE;
        }
        return FALSE;
    }

    public function getFather($user_id) {

        $this->db->select('ft.father_id,u.user_details_ref_user_id');
        $this->db->from('ft_individual as ft');
        $this->db->join('user_details as u', 'ft.id = u.user_detail_refid');
        $this->db->where('ft.id', $user_id);
        $query = $this->db->get();

        foreach ($query->result() as $row) {

            $arr['father_id'] = $row->father_id;
            $arr['referral_id'] = $row->user_detail_refid;
        }


        return $arr;
    }

    public function checkUserFirstSofartRecharge($user_id) {

        $id = '';
        $this->db->select('*');
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('monthly_fee_history');

        foreach ($query->result() as $row) {

            $id = $row->user_id;
        }
        if ($id) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function calculateLegCount($father_id, $referral_id, $recharge_amount, $user_id) {

        $this->obj_calc->calculateLegCount($father_id, $referral_id, $recharge_amount, $user_id);
    }

    public function isPositionAvailable($placement, $position) {
        $this->db->select('*');
        $this->db->where('placement', $placement);
        $this->db->where('position', $position);
        $this->db->not_like('position', 0);
        $this->db->not_like('status', 'cancel');
        $res = $this->db->get('temp_reg_details');
        if ($res->num_rows() > 0)
            return FALSE;
        else
            return TRUE;
    }

    public function isPositionAvailableUser($placement, $position) {
        $this->db->select('*');
        $this->db->where('placement', $placement);
        $this->db->where('position', $position);
        $this->db->not_like('position', 0);
        $this->db->like('status', 'no');
        $res = $this->db->get('temp_reg_details');
        if ($res->num_rows() > 0)
            return FALSE;
        else
            return TRUE;
    }
    
    
    

}

?>

