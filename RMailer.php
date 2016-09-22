<?php

/*	USAGE:

	$mailer = new RMailer(array(
		'smtpHost'     => 'localhost',
		'smtpUsername' => null,
		'smtpPassword' => null
	));

	$mailer->from = array('sender@example.com' => 'Sender Smith');
	$mailer->to = array(
		array('userb@richtest.com' => 'Sender Smith B'),
		array('usera@richtest.com' => 'Sender Smith A'),
	);
	$mailer->cc = array(
		array('userc@richtest.com' => 'Sender Smith 5'),
		array('userd@richtest.com' => 'Sender Smith 6 '),
	);
	$mailer->bcc = array(
		array('usere@richtest.com' => 'Sender Smit 2345'),
	);
		

	$mailer->subject = "Blah Blah";
	$mailer->html = "<h1>This is an email</h1><p>This is a paragrapha</p>";
	$mailer->text = "This is an email\n\nThis is a paragrapha";
	if(!$mailer->send()) {
		echo $mailer->ErrorMessage;
	} else {
		// success
	}


*/

require_once('PHPMailer/class.phpmailer.php');

class RMailer
{
	public $ErrorMessage = null;

	// these are strings
	private $smtpHost;
	private $smtpUsername;
	private $smtpPassword;

	// these are strings
	private $from;
	private $from_name;

	// these are arrays of assoc arrays
	private $to;
	private $cc;
	private $bcc;

	// these are strings
	private $subject;
	private $htmlPart;
	private $textPart;

	public function __construct($args=null)
	{
		$this->smtpHost      = $args['smtpHost']     ? $args['smtpHost']     : Yii::app()->params['smtpHost'];
		$this->smtpUsername  = $args['smtpUsername'] ? $args['smtpUsername'] : Yii::app()->params['smtpUsername'];
		$this->smtpPassword  = $args['smtpPassword'] ? $args['smtpPassword'] : Yii::app()->params['smtpPassword'];

		$this->from          = $args['from']         ? $args['from']         : Yii::app()->params['adminEmail'];
		$this->from_name     = $args['from_name']    ? $args['from_name']    : Yii::app()->params['adminName'];

		$this->to = array();
		$this->cc = array();
		$this->bcc = array();
	}

	public function setFrom($val)
	{
		if(is_array($val))
		{
			$this->from = key($val);
			if(array_key_exists($this->from, $val))
			{
				$this->from_name = $val[$this->from];
			}
		}
		else
		{
			$this->from = $val;
			$this->from_name = null;
		}
	}

	public function setTo($recips)
	{
		$this->to = $recips;
	}

	public function setCc($recips)
	{
		$this->cc = $recips;
	}

	public function setBcc($recips)
	{
		$this->bcc = $recips;
	}

	private function checkInfo()
	{

		if( $this->from && sizeof($this->to) > 0 &&
			$this->smtpHost &&
			( $this->textPart || $this->htmlPart ) )
		{
			return true;
		}
		else
		{
			return false;
		}

	}

	public function setSubject($subject)
	{
		$this->subject = $subject;
	}

	public function setHtml($content)
	{
		$this->htmlPart = $content;
	}

	public function setText($content)
	{
		$this->textPart = $content;
	}

	public function send()
	{
				
		// check if we have enough info
		if(!$this->checkInfo())
		{
			throw new Exception(sprintf("Not enough information given to send email [%s] [%s] [%s] [%s] [%s]",
					$this->from,
					$this->to,
					$this->smtpHost,
					$this->textPart,
					$this->htmlPart
				)
			);
		}

		// send the activation email
		try {
			$mail = new PHPMailer(true); // NOTE - passing true turns on exceptions, and turns off error to STDOUT

			$mail->IsSMTP();
			$mail->Host = $this->smtpHost;
			// $mail->SMTPDebug = 3;
			
			// SMTP auth only if we have a username and password
			$mail->SMTPAuth = ($this->smtpUsername && $this->smtpPassword);
			$mail->Username = $this->smtpUsername;
			$mail->Password = $this->smtpPassword;

			// Sender

			//// From Header
			$mail->From = $this->from;		
			$mail->FromName = $this->from_name;

			//// MAIL FROM
			$mail->Sender = $this->from;
			$mail->AddReplyTo($this->from, $this->from_name);
			
			// Recipients
			foreach ($this->to as $item) {
				$address = key($item);
				$mail->AddAddress($address, $item[$address]);
			}
			foreach ($this->cc as $item) {
				$address = key($item);
				$mail->AddCC($address, $item[$address]);
			}
			foreach ($this->bcc as $item) {
				$address = key($item);
				$mail->AddBCC($address, $item[$address]);
			}

			$mail->WordWrap = 50;                                 // set word wrap to 50 characters
			$mail->IsHTML($this->htmlPart);

			$mail->Subject = $this->subject;
			$mail->Body    = $this->htmlPart ? $this->htmlPart : htmlentities($this->textPart);
			if($this->textPart) {
				$mail->AltBody = $this->textPart;
			}

			$mail->Send();
		
		} catch (phpmailerException $e) {
		  $this->ErrorMessage = $e->errorMessage();
		  return false;
		} catch (Exception $e) {
		  $this->ErrorMessage = $e->errorMessage();
		  return false;
		}
		return true;
	}
}

?>