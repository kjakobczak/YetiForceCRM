<?php

/**
 * Action change relation data.
 *
 * @package   Action
 *
 * @copyright YetiForce Sp. z o.o
 * @license YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * @author Arkadiusz Dudek <a.dudekk@yetiforce.com>
 */
/**
 * Class ChangeRelationData.
 */
class Vtiger_ChangeRelationData_Action extends Vtiger_BasicAjax_Action
{
	/**
	 * Function to check permission.
	 *
	 * @param \App\Request $request
	 *
	 * @throws \App\Exceptions\NoPermitted
	 */
	public function checkPermission(App\Request $request)
	{
		$recordModel = \Vtiger_Record_Model::getInstanceById($request->getInteger('record'));
		if (!$recordModel->isEditable() || !Vtiger_Record_Model::getInstanceById($request->getInteger('fromRecord'))->isEditable()) {
			throw new \App\Exceptions\NoPermittedToRecord('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function process(App\Request $request): void
	{
		$recordId = $request->getInteger('record');
		$parentRecordId = $request->getInteger('fromRecord');
		$relationId = $request->getInteger('relationId');
		$relation = Vtiger_Relation_Model::getInstanceById($relationId)->getTypeRelationModel();
		$updateData = [];
		foreach ($relation->getFields() as $fieldModel) {
			if ($request->has($fieldModel->getName())) {
				$value = $request->getByType($fieldModel->getName(), 'Text');
				$fieldModel->getUITypeModel()->validate($value, true);
				$updateData[$fieldModel->getName()] = $fieldModel->getUITypeModel()->getDBValue($value);
			}
		}
		$result = $relation->updateRelationData($parentRecordId, $recordId, $updateData);
		$response = new Vtiger_Response();
		$response->setResult(\is_bool($result));
		$response->emit();
	}
}
