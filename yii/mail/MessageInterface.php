<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\mail;

/**
 * MessageInterface is an interface, which email message should apply.
 * Together with application component, which matches the [[MailerInterface]],
 * it introduces following mail sending syntax:
 * ~~~php
 * Yii::$app->mail->compose()
 *     ->from('from@domain.com')
 *     ->to('to@domain.com')
 *     ->subject('Message Subject')
 *     ->renderText('text/view')
 *     ->renderHtml('html/view')
 *     ->send();
 * ~~~
 *
 * @see MailerInterface
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
interface MessageInterface
{
	/**
	 * Set the character set of this message.
	 * @param string $charset character set name.
	 * @return static self reference.
	 */
	public function charset($charset);

	/**
	 * Sets message sender.
	 * @param string|array $from sender email address.
	 * You may pass an array of addresses if this message is from multiple people.
	 * You may also specify sender name in addition to email address using format:
	 * [email => name].
	 * @return static self reference.
	 */
	public function from($from);

	/**
	 * Sets message receiver.
	 * @param string|array $to receiver email address.
	 * You may pass an array of addresses if multiple recipients should receive this message.
	 * You may also specify receiver name in addition to email address using format:
	 * [email => name].
	 * @return static self reference.
	 */
	public function to($to);

	/**
	 * Set the Cc (additional copy receiver) addresses of this message.
	 * @param string|array $cc copy receiver email address.
	 * You may pass an array of addresses if multiple recipients should receive this message.
	 * You may also specify receiver name in addition to email address using format:
	 * [email => name].
	 * @return static self reference.
	 */
	public function cc($cc);

	/**
	 * Set the Bcc (hidden copy receiver) addresses of this message.
	 * @param string|array $bcc hidden copy receiver email address.
	 * You may pass an array of addresses if multiple recipients should receive this message.
	 * You may also specify receiver name in addition to email address using format:
	 * [email => name].
	 * @return static self reference.
	 */
	public function bcc($bcc);

	/**
	 * Sets message subject.
	 * @param string $subject message subject
	 * @return static self reference.
	 */
	public function subject($subject);

	/**
	 * Sets message plain text content.
	 * @param string $text message plain text content.
	 * @return static self reference.
	 */
	public function text($text);

	/**
	 * Sets message HTML content.
	 * @param string $html message HTML content.
	 * @return static self reference.
	 */
	public function html($html);

	/**
	 * Attaches existing file to the email message.
	 * @param string $fileName full file name
	 * @param array $options options for embed file. Valid options are:
	 * - fileName: name, which should be used to attach file.
	 * - contentType: attached file MIME type.
	 * @return static self reference.
	 */
	public function attach($fileName, array $options = []);

	/**
	 * Attach specified content as file for the email message.
	 * @param string $content attachment file content.
	 * @param array $options options for embed file. Valid options are:
	 * - fileName: name, which should be used to attach file.
	 * - contentType: attached file MIME type.
	 * @return static self reference.
	 */
	public function attachContent($content, array $options = []);

	/**
	 * Attach a file and return it's CID source.
	 * This method should be used when embedding images or other data in a message.
	 * @param string $fileName file name.
	 * @param array $options options for embed file. Valid options are:
	 * - fileName: name, which should be used to attach file.
	 * - contentType: attached file MIME type.
	 * @return string attachment CID.
	 */
	public function embed($fileName, array $options = []);

	/**
	 * Attach a content as file and return it's CID source.
	 * This method should be used when embedding images or other data in a message.
	 * @param string $content  attachment file content.
	 * @param array $options options for embed file. Valid options are:
	 * - fileName: name, which should be used to attach file.
	 * - contentType: attached file MIME type.
	 * @return string attachment CID.
	 */
	public function embedContent($content, array $options = []);

	/**
	 * Sends this email message.
	 * @return boolean success.
	 */
	public function send();

	/**
	 * Returns string representation of this message.
	 * @return string the string representation of this message.
	 */
	public function toString();
}