<?php

/**
 * ownCloud - Activity App
 *
 * @author Joas Schilling
 * @copyright 2014 Joas Schilling nickvergessen@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Activity;

/**
 * Class Api
 *
 * @package OCA\Activity
 */
class Api
{
	const DEFAULT_LIMIT = 30;

	static public function get() {
		$app = new AppInfo\Application();
		/** @var Data $data */
		$data = $app->getContainer()->query('ActivityData');

		$start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
		$count = isset($_GET['count']) ? (int) $_GET['count'] : self::DEFAULT_LIMIT;
		$user = $app->getContainer()->getServer()->getUserSession()->getUser()->getUID();

		if ($start !== 0) {
			$start = self::getSinceFromOffset($user, $start);
		}

		$activities = $data->get(
			$app->getContainer()->query('GroupHelper'),
			$app->getContainer()->query('UserSettings'),
			$user, $start, $count, 'desc', 'all'
		);

		$entries = array();
		foreach($activities['data'] as $entry) {
			$entries[] = array(
				'id' => $entry['activity_id'],
				'subject' => (string) $entry['subjectformatted']['full'],
				'message' => (string) $entry['messageformatted']['full'],
				'file' => $entry['object_name'],
				'link' => $entry['link'],
				'date' => date('c', $entry['timestamp']),
			);
		}

		return new \OC_OCS_Result($entries);
	}

	/**
	 * @param string $user
	 * @param int $offset
	 * @return int
	 */
	protected static function getSinceFromOffset($user, $offset) {
		$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$query->select('activity_id')
			->from('activity')
			->where($query->expr()->eq('affecteduser', $query->createNamedParameter($user)))
			->orderBy('activity_id', 'desc')
			->setFirstResult($offset - 1)
			->setMaxResults(1);

		$result = $query->execute();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row) {
			return (int) $row['activity_id'];
		}

		return 0;
	}
}
