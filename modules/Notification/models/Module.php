<?php

/**
 * Notification Record Model
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Notification_Module_Model extends Vtiger_Module_Model
{

	/**
	 * Function create message contents
	 * @return int
	 */
	public static function getNumberOfEntries()
	{
		$count = (new App\Db\Query())->from('u_#__notification')
			->innerJoin('vtiger_crmentity', 'u_#__notification.id = vtiger_crmentity.crmid')
			->where(['vtiger_crmentity.smownerid' => Users_Record_Model::getCurrentUserModel()->getId(), 'vtiger_crmentity.deleted' => 0, 'notification_status' => 'PLL_UNREAD'])
			->count();
		$max = AppConfig::module('Home', 'MAX_NUMBER_NOTIFICATIONS');
		return $count > $max ? $max : $count;
	}

	public function getEntries($limit = false, $conditions = false)
	{
		$queryGenerator = new App\QueryGenerator($this->getName());
		$queryGenerator->setFields(['description', 'smwonerid', 'id', 'title', 'link', 'process', 'subprocess', 'createdtime', 'notification_type', 'smcreatorid']);
		$queryGenerator->addAndConditionNative(['smownerid' => \App\User::getCurrentUserId()]);
		if (!empty($conditions)) {
			$queryGenerator->addAndConditionNative($conditions);
		}
		$queryGenerator->addAndConditionNative(['u_#__notification.notification_status' => 'PLL_UNREAD']);
		$query = $queryGenerator->createQuery();
		if (!empty($limit)) {
			$query->limit($limit);
		}
		$dataReader = $query->createCommand()->query();
		$entries = [];
		while ($row = $dataReader->read()) {
			$recordModel = Vtiger_Record_Model::getCleanInstance('Notification');
			$recordModel->setData($row);
			if ($groupBy) {
				$entries[$row['type']][$row['id']] = $recordModel;
			} else {
				$entries[$row['id']] = $recordModel;
			}
		}
		return $entries;
	}

	/**
	 * Function gets notifications to be sent
	 * @param int $userId
	 * @param array $modules
	 * @param string $startDate
	 * @param string $endDate
	 * @param boolean $isExists
	 * @return array|boolean
	 */
	public static function getEmailSendEntries($userId, $modules, $startDate, $endDate, $isExists = false)
	{
		$query = (new \App\Db\Query())
			->from('u_#__notification')
			->innerJoin('vtiger_crmentity', 'u_#__notification.id = vtiger_crmentity.crmid')
			->leftJoin('vtiger_crmentity as crmlink', 'u_#__notification.link = crmlink.crmid')
			->leftJoin('vtiger_crmentity as crmprocess', 'u_#__notification.process = crmprocess.crmid')
			->leftJoin('vtiger_crmentity as crmsubprocess', 'u_#__notification.subprocess = crmsubprocess.crmid')
			->where(['vtiger_crmentity.deleted' => 0, 'vtiger_crmentity.smownerid' => $userId])
			->andWhere(['or', ['in', 'crmlink.setype', $modules], ['in', 'crmprocess.setype', $modules], ['in', 'crmsubprocess.setype', $modules]])
			->andWhere(['between', 'vtiger_crmentity.createdtime', (string) $startDate, $endDate])
			->andWhere(['notification_status' => 'PLL_UNREAD']);
		if ($isExists) {
			return $query->exists();
		}
		$query->select(['u_#__notification.*', 'vtiger_crmentity.*']);
		$dataReader = $query->createCommand()->query();
		$entries = [];
		while ($row = $dataReader->read()) {
			$recordModel = Vtiger_Record_Model::getCleanInstance('Notification');
			$recordModel->setData($row);
			$entries[$row['notification_type']][$row['id']] = $recordModel;
		}
		return $entries;
	}

	/**
	 * Function to get types of notification
	 * @return array
	 */
	public function getTypes()
	{
		$fieldModel = Vtiger_Field_Model::getInstance('notification_type', Vtiger_Module_Model::getInstance('Notification'));
		return $fieldModel->getPicklistValues();
	}
}
