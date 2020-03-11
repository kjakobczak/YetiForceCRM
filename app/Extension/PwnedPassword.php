<?php

/**
 * Pwned password file to check the password.
 *
 * @package   App
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

namespace App\Extension;

/**
 * Pwned password class to check the password.
 */
class PwnedPassword
{
	/**
	 * Check the password function.
	 *
	 * @param string $password
	 *
	 * @return array ['message' => (string) , 'status' => (bool)]
	 */
	public static function check(string $password): array
	{
		return self::getDefaultProvider()->check($password);
	}

	/**
	 * Get all providers.
	 *
	 * @return []\App\Extension\PwnedPassword\Base
	 */
	public static function getProviders(): array
	{
		$return = [];
		foreach (new \DirectoryIterator(ROOT_DIRECTORY . '/app/Extension/PwnedPassword/') as $item) {
			if ($item->isFile() && 'Base' !== $item->getBasename('.php')) {
				$fileName = $item->getBasename('.php');
				$className = "\\App\\Extension\\PwnedPassword\\$fileName";
				$instance = new $className();
				$return[$fileName] = $instance;
			}
		}
		return $return;
	}

	/**
	 * Get default provider.
	 *
	 * @return \App\Extension\PwnedPassword\Base
	 */
	public static function getDefaultProvider(): PwnedPassword\Base
	{
		$className = '\\App\\Extension\\PwnedPassword\\' . \App\Config::module('Users', 'pwnedPasswordProvider');
		if (!class_exists($className)) {
			throw new \App\Exceptions\AppException('ERR_CLASS_NOT_FOUND');
		}
		return new $className();
	}

	/**
	 * Check the password after login.
	 *
	 * @param string $password
	 *
	 * @return void
	 */
	public static function afterLogin(string $password): void
	{
		$file = ROOT_DIRECTORY . '/app_data/PwnedPassword.php';
		$userName = \App\Session::get('user_name');
		$time = (int) \Settings_Password_Record_Model::getUserPassConfig()['pwned_time'] ?? 0;
		if (!$time) {
			return;
		}
		$pwnedDates = [];
		if (file_exists($file)) {
			$pwnedDates = require $file;
		}
		if (empty($pwnedDates[$userName]) || strtotime($pwnedDates[$userName]) < strtotime("-$time day")) {
			if (($passStatus = self::check($password)) && !$passStatus['status']) {
				\App\Session::set('ShowUserPwnedPasswordChange', 1);
			}
			$pwnedDates[$userName] = date('Y-m-d H:i:s');
			\App\Utils::saveToFile($file, $pwnedDates, '', 0, true);
		}
	}
}