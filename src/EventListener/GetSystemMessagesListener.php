<?php
// src/EventListener/GetSystemMessagesListener.php
//namespace App\EventListener;
namespace Delirius\ContaoBackendNotes\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\StringUtil;

#[AsHook('getSystemMessages')]
class GetSystemMessagesListener {
	public function __invoke(): string {
		$arrReturn = [];

		// Display a warning if the system admin's email is not set
		// if (empty($GLOBALS['TL_ADMIN_EMAIL'])) {
		// 	$arrReturn[] = '<p class="tl_error">Please add your email address to system settings.</p>';
		// }

		// Get the database connection
		$db = \Contao\System::getContainer()->get('database_connection');

		$query = 'SELECT * FROM tl_backend_notes WHERE published = ? ORDER BY sorting';
		$stmt = $db->executeQuery($query, [1]);

		while (false !== ($row = $stmt->fetchAssociative())) {
			$arrReturn[] = GetSystemMessagesListener::output($row);

		}

		if ( ! empty($arrReturn)) {
			return implode('', $arrReturn);
		} else {
			return '';
		}
	}

	public function output($r): string {
		$bgcolor = ($r['bgcolor'] ? $r['bgcolor'] : 'fffbd9');

		$out = '<div style="background-color:#' . $bgcolor . ';padding:0.6rem;margin-bottom:1rem">';
		$out .= '<strong style="font-size:1rem;margin-bottom:0.6rem;display:block;">' . $r['title'] . '</strong>';
		if ($r['addImage']) {

			$arrImages = StringUtil::deserialize($r['orderSRC']);

			if (isset($arrImages)) {
				$strimages = '';
				$strimages .= '<div style="display:flex; flex-wrap:wrap; align-items: flex-start; gap:0.6rem">';
				foreach ($arrImages as $img) {
					$objFile = \Contao\FilesModel::findByUuid($img);

					if ($objFile !== \null  && \file_exists(\TL_ROOT  . '/' . $objFile->path)) {
						if ($r['imagelink']) {
							$strimages .= '<a href="' . $objFile->path . '" target="_blank" style="border:1px solid #3366cc">';
						}

						$projectDir = \Contao\System::getContainer()->getParameter('kernel.project_dir');
						$strimages .= \Contao\Image::getHtml(\Contao\System::getContainer()->get('contao.image.factory')->create($projectDir . '/' . $objFile->path, $r['size'])->getUrl($projectDir), '', '');

						if ($r['imagelink']) {
							$strimages .= '</a>';
						}

					}

				}
				$strimages .= '</div>';

				$out .= $strimages;

			}

		}
		$out .= '<p>';
		$out .= $r['textarea'];
		$out .= '</p>';
		$out .= '</div>';

		return $out;
	}
}
?>