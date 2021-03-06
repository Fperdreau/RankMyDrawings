<?php
/*
Copyright © 2014, F. Perdreau, Radboud University Nijmegen
=======
This file is part of RankMyDrawings.

RankMyDrawings is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

RankMyDrawings is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with RankMyDrawings.  If not, see <http://www.gnu.org/licenses/>.

*/

// Import html2text class
require_once(PATH_TO_LIBS."html2text-0.2.2/html2text.php");
require_once(PATH_TO_LIBS.'PHPMailer-master/class.phpmailer.php');
require_once(PATH_TO_LIBS.'PHPMailer-master/class.smtp.php');

class AppMail {
    /**
     * @inject $db
     */
    protected $db;
    protected $tablename;

    /**
     * @inject $config
     */
    private $config;

    /**
     * @var bool
     */
    private $SMTPDebug = false;

    /**
     * Class constructor
     * @param AppDb $db
     * @param AppConfig $config
     */
    function __construct(AppDb $db, AppConfig $config) {
        $this->db = $db;
        $this->tablename = $this->db->tablesname['Users'];
        $this->config = $config;
    }

    /**
     * Send a verification email to organizers when someone signed up to the application.
     * @param $hash
     * @param $user_mail
     * @param $username
     * @return bool
     */
    function send_verification_mail($hash,$user_mail,$username) {
        $Users = new Users($this->db);
        $admins = $Users->getadmin('admin');
        $to = array();
        for ($i=0; $i<count($admins); $i++) {
            $to[] = $admins[$i]['email'];
        }

        $subject = 'Signup | Verification'; // Give the email a subject
        $authorize_url = $this->config->site_url."index.php?page=verify&email=$user_mail&hash=$hash&result=true";
        $deny_url = $this->config->site_url."index.php?page=verify&email=$user_mail&hash=$hash&result=false";

        $content = "
        Hello,<br><br>
        <p><b>$username</b> wants to create an account.</p>
        <p><a href='$authorize_url'>Authorize</a></p>
        or
        <p><a href='$deny_url'>Deny</a></p>
        <p>The Journal Club Team</p>
        ";

        $body = self::formatmail($content);
        return self::send_mail($to,$subject,$body);
    }

    /**
     * Send an email to the user if his/her account has been deactivated due to too many login attempts.
     * @param User $user
     * @return bool
     */
    function send_activation_mail(User $user) {
        $to = $user->email;
        $subject = 'Your account has been deactivated'; // Give the email a subject
        $authorize_url = $this->config->site_url."index.php?page=verify&email=$user->email&hash=$user->hash&result=true";
        $newpwurl = $this->config->site_url."index.php?page=renew_pwd&hash=$user->hash&email=$user->email";
        $content = "
        <p>Hello <b>$user->fullname</b>,</p>
        <p>We have the regret to inform you that your account has been deactivated due to too many login attempts.</p>
        <p>You can reactivate your account by following this link:<br>
        <a href='$authorize_url'>$authorize_url</a>
        </p>
        <p>If you forgot your password, you can ask for another one here:<br>
        <a href='$newpwurl'>$newpwurl</a>
        </p>
        <p>Cheers,<br>The Journal Club Team</p>
        ";

        $body = self::formatmail($content);
        return self::send_mail($to,$subject,$body);
    }

    /**
     * Send a confirmation email to the new user once his/her registration has been validated by an organizer
     * @param $to
     * @param $username
     * @return bool
     */
    function send_confirmation_mail($to,$username) {
        /** @var User $user */
        $user = new User($this->db);
        $user->get($username);

        $subject = 'Signup | Confirmation'; // Give the email a subject
        $login_url = $this->config->site_url."index.php";

        $content = "
        Hello $user->fullname,<br><br>
        Thank you for signing up!<br>
        <p>Your account has been created, you can now <a href='$login_url'>log in</a> with the following credentials.</p>
        <p>------------------------<br>
        <b>Username</b>: $username<br>
        <b>Password</b>: Only you know it!<br>
        ------------------------</p>
        <p>The Journal Club Team</p>
        ";
        $body = self::formatmail($content);
        return self::send_mail($to,$subject,$body);
    }

    /**
     * Get list of users' email
     * @param null $type
     * @return array
     */
    function get_mailinglist($type=null) {
        $sql = "select username,email from $this->tablename where active=1";
        if (null!=$type) {
            $sql .= " and $type=1";
        }

        $req = $this->db->send_query($sql);
        $mailing_list = array();
        while ($data = mysqli_fetch_array($req)) {
            $cur_mail = $data['email'];
            $mailing_list[] = $cur_mail;
        }
        return $mailing_list;
    }

    /**
     * Send an email to the whole mailing list (but only to users who agreed upon receiving emails)
     * @param $subject
     * @param $body
     * @param null $type
     * @param null $attachment
     * @return bool
     */
    function send_to_mailinglist($subject,$body,$type=null,$attachment = NULL) {
        $to = $this->get_mailinglist($type);
        if ($this->send_mail($to,$subject,$body,$attachment)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Send an email
     * @param $to
     * @param $subject
     * @param $body
     * @param null $attachment
     * @return bool
     * @throws Exception
     * @throws phpmailerException
     */
    function send_mail($to,$subject,$body,$attachment = NULL) {
        $mail = new PHPMailer();

        $mail->IsSMTP();                                      // set mailer to use SMTP
        $mail->SMTPDebug  = $this->SMTPDebug;         // enables SMTP debug information (for testing)

        $mail->Mailer = "smtp";
        $mail->Host = $this->config->mail_host;
        $mail->Port = $this->config->mail_port;

        if ($this->config->SMTP_secure != "none") {
            $mail->SMTPAuth = true;     // turn on SMTP authentication
            $mail->SMTPSecure = $this->config->SMTP_secure; // secure transfer enabled REQUIRED for GMail
            $mail->Username = $this->config->mail_username;
            $mail->Password = $this->config->mail_password;
        }

        $mail->From = $this->config->mail_from;
        $mail->FromName = $this->config->mail_from_name;

        $mail->AddAddress("undisclosed-recipients:;");
        $mail->AddReplyTo($this->config->mail_from, $this->config->mail_from_name);

        if (is_array($to)) {
            foreach($to as $to_add){
                $mail->AddBCC($to_add);                  // name is optional
            }
        } else {
            $mail->AddBCC($to);                  // name is optional
        }

        $mail->WordWrap = 50;                                 // set word wrap to 50 characters
        $mail->IsHTML(true);

        $mail->Subject = $this->config->pre_header." ".$subject;
        $mail->Body    = $body;
        $mail->AltBody= @convert_html_to_text($body); // Convert to plain text for email viewers non-compatible with HTML content

        if($attachment != null){
            if (!$mail->AddAttachment($attachment)) {
                return false;
            }
        }

        if ($rep = $mail->Send()) {
            $mail->ClearAddresses();
            $mail->ClearAttachments();
            return true;
        } else {
            return false;
        }

    }

    /**
     * Format email (html)
     * @param $content
     * @return string
     */
    function formatmail($content) {
        $profile_url = $this->config->site_url.'index.php?page=profile';
        $sitetitle = $this->config->sitetitle;
        $body = "
            <div style='font-family: Helvetica Neue, Helvetica, Arial, sans-serif sans-serif; background-color: #cccccc; color: #222222; font-weight: 300; font-size: 12px; min-width: 600px; margin: auto;'>
                <div style='line-height: 1.2; width: 100%; color: #000000;'>
                    <div style='font-size: 30px; color: #cccccc; line-height: 40px; height: 40px; text-align: center; background-color: #555555;'>$sitetitle
                    </div>

                    <div style='padding: 10px; margin: auto; text-align: justify; background-color: rgba(255,255,255,.5);'>
                        $content
                    </div>

                    <div style='color: #EEEEEE; width: 100%; min-height: 30px; height: auto; line-height: 30px; text-align: center; background-color: #555555;'>
                        This email has been sent automatically. You can choose to no longer receive notification emails from your
                        <a href='$profile_url' style='color: #CF5151; text-decoration: none;' target='_blank' >profile</a> page.
                    </div>
                </div>
            </div>";
        return $body;
    }
}
