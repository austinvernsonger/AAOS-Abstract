<?php

/**
 * @file pages/admin/AdminPeopleHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminPeopleHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for people management functions.
 */


import('pages.admin.AdminHandler');

class AdminPeopleHandler extends AdminHandler {
	/**
	 * Constructor
	 */
	function AdminPeopleHandler() {
		parent::AdminHandler();
	}

	/**
	 * Allow the Site Administrator to merge user accounts.
	 */
	function mergeUsers($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$templateMgr =& TemplateManager::getManager();

		$oldUserIds = (array) $request->getUserVar('oldUserIds');
		$newUserId = $request->getUserVar('newUserId');

		if (!empty($oldUserIds) && !empty($newUserId)) {
			import('classes.user.UserAction');
			foreach ($oldUserIds as $oldUserId) {
				UserAction::mergeUsers($oldUserId, $newUserId);
			}
			$request->redirect(null, null, 'admin', 'mergeUsers');
		}

		// The administrator must select one or both IDs.
		if ($request->getUserVar('roleSymbolic')!=null) $roleSymbolic = $request->getUserVar('roleSymbolic');
		else $roleSymbolic = isset($args[0])?$args[0]:'all';

		if ($roleSymbolic != 'all' && String::regexp_match_get('/^(\w+)s$/', $roleSymbolic, $matches)) {
			$roleId = $roleDao->getRoleIdFromPath($matches[1]);
			if ($roleId == null) {
				$request->redirect(null, null, null, null, 'all');
			}
			$roleName = $roleDao->getRoleName($roleId, true);
		} else {
			$roleId = 0;
			$roleName = 'admin.mergeUsers.allUsers';
		}

		$searchType = null;
		$searchMatch = null;
		$search = $request->getUserVar('search');
		$searchInitial = $request->getUserVar('searchInitial');
		if (!empty($search)) {
			$searchType = $request->getUserVar('searchField');
			$searchMatch = $request->getUserVar('searchMatch');

		} elseif (!empty($searchInitial)) {
			$searchInitial = String::strtoupper($searchInitial);
			$searchType = USER_FIELD_INITIAL;
			$search = $searchInitial;
		}

		$rangeInfo =& Handler::getRangeInfo($request, 'users', array($roleId, (string) $search, (string) $searchMatch, (string) $searchType));

		if ($roleId) {
			while (true) {
				$users =& $roleDao->getUsersByRoleId($roleId, null, null, $searchType, $search, $searchMatch, $rangeInfo);
				if ($users->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $users->getLastPageRangeInfo();
				unset($users);
			}
			$templateMgr->assign('roleId', $roleId);
		} else {
			while (true) {
				$users =& $userDao->getUsersByField($searchType, $searchMatch, $search, true, $rangeInfo);
				if ($users->isInBounds()) break;
				unset($rangeInfo);
				$rangeInfo =& $users->getLastPageRangeInfo();
				unset($users);
			}
		}

		$templateMgr->assign('currentUrl', $request->url(null, null, 'admin', 'mergeUsers'));
		$templateMgr->assign('helpTopicId', 'site.administrativeFunctions');
		$templateMgr->assign('roleName', $roleName);
		$templateMgr->assign_by_ref('users', $users);
		$templateMgr->assign_by_ref('thisUser', $request->getUser());
		$templateMgr->assign('isReviewer', $roleId == ROLE_ID_REVIEWER);

		$templateMgr->assign('searchField', $searchType);
		$templateMgr->assign('searchMatch', $searchMatch);
		$templateMgr->assign('search', $search);
		$templateMgr->assign('searchInitial', $request->getUserVar('searchInitial'));

		$templateMgr->assign('fieldOptions', Array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email',
			USER_FIELD_INTERESTS => 'user.interests'
		));
		$templateMgr->assign('alphaList', explode(' ', __('common.alphaList')));
		$templateMgr->assign('oldUserIds', $oldUserIds);
		$role =& $roleDao->newDataObject();
		$role->setId($roleId);
		$templateMgr->assign('rolePath', $role->getPath());
		$templateMgr->assign('roleSymbolic', $roleSymbolic);
		$templateMgr->display('admin/selectMergeUser.tpl');
	}
}

?>
