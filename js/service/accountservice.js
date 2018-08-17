/* global Promise */

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015, 2017
 */

const $ = require('jquery');
const OC = require('OC');

export function createAccount (config) {
	var url = OC.generateUrl('apps/mail/api/accounts');
	return new Promise((resolve, reject) => {
		$.ajax(url, {
			data: config,
			type: 'POST',
			success: resolve,
			error: (jqXHR, textStatus, errorThrown) => {
				switch (jqXHR.status) {
					case 400:
						var response = JSON.parse(jqXHR.responseText);
						reject(t('mail', 'Error while creating the account: ' + response.message));
						break;
					default:
						var error = errorThrown || textStatus || t('mail', 'Unknown error');
						reject(t('mail', 'Error while creating the account: ' + error));
				}
			}
		});
	});
}

/**
 * @private
 * @returns {Promise}
 */
export function loadAccountData () {
	var $serialized = $('#serialized-accounts');
	var accounts = require('state').accounts;

	if ($serialized.val() !== '') {
		var serialized = $serialized.val();
		var serialzedAccounts = JSON.parse(atob(serialized));

		accounts.reset();
		for (var i = 0; i < serialzedAccounts.length; i++) {
			accounts.add(serialzedAccounts[i]);
		}
		$serialized.val('');
		return Promise.resolve(accounts);
	}

	return new Promise((resolve, reject) => {
		accounts.fetch({
			success: () => {
				// fetch resolves the Promise with the raw data returned by
				// the ajax call. Since we want the Backbone models, we have
				// to 'convert' the response here.
				resolve(accounts);
			},
			error: reject
		});
	});
}

/**
 * @returns {Promise}
 */
export function getAccountEntities () {
	return loadAccountData().then((accounts) => {
		if (accounts.length > 1) {
			accounts.add({
				accountId: -1,
				isUnified: true
			}, {
				at: 0
			});
		}

		return accounts;
	});
}

/**
 * @param {Account} account
 * @returns {Promise}
 */
export function deleteAccount (account) {
	var url = OC.generateUrl('/apps/mail/api/accounts/{accountId}', {
		accountId: account.get('accountId')
	});

	return Promise.resolve($.ajax(url, {
		type: 'DELETE'
	}));
}

/**
 * @param {Account} account
 * @returns {Promise}
 */
export function updateAccount (config) {

	var url = OC.generateUrl('/apps/mail/api/accounts/{accountId}', {
		accountId: config.accountId
	});

	return new Promise((resolve, reject) => {
		$.ajax(url, {
			data: config,
			type: 'PUT',
			success: resolve,
			error: (jqXHR, textStatus, errorThrown) => {
				switch (jqXHR.status) {
					case 400:
						var response = JSON.parse(jqXHR.responseText);
						reject(t('mail', 'Error while updating the account: ' + response.message));
						break;
					default:
						var error = errorThrown || textStatus || t('mail', 'Unknown error');
						reject(t('mail', 'Error while updating the account: ' + error));
				}
			}
		});
	});
}
