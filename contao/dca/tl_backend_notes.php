<?php

declare (strict_types = 1);

/*
 * This file is part of Backend notes.
 *
 * (c) Daniel Herren 2023 <contao@delirius.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/delirius/contao-backend-notes
 */

use Contao\Backend;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;

/**
 * Table tl_backend_notes
 */
$GLOBALS['TL_DCA']['tl_backend_notes'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
		'enableVersioning' => true,
		'sql' => array(
			'keys' => array(
				'id' => 'primary',
			),
		),
	),
	'list' => array(
		'sorting' => array(
			'mode' => DataContainer::MODE_TREE,
			'fields' => array('sorting'),
			'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout' => 'filter;sort,search,limit',
		),
		'label' => array(
			'fields' => array('title'),
			'format' => '%s',
			'label_callback' => array('tl_backend_notes_ext', 'addIcon'),

		),
		'global_operations' => array(
			'all' => array(
				'href' => 'act=select',
				'class' => 'header_edit_all',
				'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
			),
		),
		'operations' => array(
			'edit' => array(
				'href' => 'act=edit',
				'icon' => 'edit.svg',
			),
			'copy' => array(
				'href' => 'act=copy',
				'icon' => 'copy.svg',
			),
			'cut' => array
			(
				'href' => 'act=paste&amp;mode=cut',
				'icon' => 'cut.svg',
				'attributes' => 'onclick="Backend.getScrollOffset()"',
			),
			'delete' => array(
				'href' => 'act=delete',
				'icon' => 'delete.svg',
				'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
			),
			'toggle' => array
			(
				'href' => 'act=toggle&amp;field=published',
				'icon' => 'visible.svg',
				'button_callback' => array('tl_backend_notes_ext', 'toggleIcon'),
			),

		),
	),
	'palettes' => array(
		'__selector__' => array('addImage'),
		'default' => '{first_legend},title,textarea;{image_legend},addImage;bgcolor,published',
	),
	'subpalettes' => array(
		'addImage' => 'multiSRC,size,imagelink',
	),
	'fields' => array(
		'id' => array(
			'sql' => "int(10) unsigned NOT NULL auto_increment",
		),
		'pid' => array(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'tstamp' => array(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'sorting' => array(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'published' => array
		(
			'toggle' => true,
			'filter' => true,
			'inputType' => 'checkbox',
			'eval' => array('doNotCopy' => true, 'tl_class' => 'clr w50'),
			'sql' => "char(1) NOT NULL default ''",
		),
		'title' => array(
			'inputType' => 'text',
			'search' => true,
			'sorting' => true,
			'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
			'eval' => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
			'sql' => "varchar(255) NOT NULL default ''",
		),
		'textarea' => array(
			'inputType' => 'textarea',
			'search' => true,
			'sorting' => true,
			'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
			'sql' => 'text NULL',
		),
		'multiSRC' => array
		(
			'inputType' => 'fileTree',
			'eval' => array('multiple' => true, 'fieldType' => 'checkbox', 'orderField' => 'orderSRC', 'isGallery' => true, 'isSortable' => true, 'extensions' => '%contao.image.valid_extensions%', 'files' => true, 'tl_class' => 'clr'),
			'sql' => "blob NULL",
		),
		'orderSRC' => array
		(
			'label' => &$GLOBALS['TL_LANG']['MSC']['sortOrder'],
			'sql' => "blob NULL",
		),

		'size' => array
		(
			'label' => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
			'inputType' => 'imageSize',
			'reference' => &$GLOBALS['TL_LANG']['MSC'],
			'eval' => array('rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50 clr'),
			'options_callback' => static function () {
				return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
			},
			'sql' => "varchar(64) NOT NULL default ''",
		),
		'imagelink' => array
		(
			'toggle' => true,
			'inputType' => 'checkbox',
			'eval' => array('tl_class' => 'w50 cbx m12'),
			'sql' => "char(1) NOT NULL default ''",
		),
		'bgcolor' => array(
			'sorting' => true,
			'inputType' => 'text',
			'default' => 'fffbd9',
			'eval' => array('maxlength' => 6, 'colorpicker' => true, 'isHexColor' => true, 'decodeEntities' => true, 'tl_class' => 'clr w50 wizard'),
			'sql' => "varchar(6) NOT NULL default ''",

		),
		'addImage' => array(
			'inputType' => 'checkbox',
			'eval' => array('submitOnChange' => true, 'tl_class' => 'w50 clr'),
			'sql' => "char(1) NOT NULL default ''",
		),
	),
);
class tl_backend_notes_ext extends Backend {

	public function toggleIcon($row, $href, $label, $title, $icon, $attributes) {
		$security = System::getContainer()->get('security.helper');

		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if ( ! $security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_backend_notes::published')) {
			return '';
		}

		$href .= '&amp;id=' . $row['id'];

		if ( ! $row['published']) {
			$icon = 'invisible.svg';
		}

		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.getScrollOffset();return AjaxRequest.toggleField(this,true)">' . Image::getHtml($icon, $label, 'data-icon="' . Image::getPath('visible.svg') . '" data-icon-disabled="' . Image::getPath('invisible.svg') . '" data-state="' . ($row['published'] ? 1 : 0) . '"') . '</a> ';
	}

	/**
	 * Add an image to each page in the tree
	 *
	 * @param array         $row
	 * @param string        $label
	 * @param DataContainer $dc
	 * @param string        $imageAttribute
	 * @param boolean       $blnReturnImage
	 * @param boolean       $blnProtected
	 * @param boolean       $isVisibleRootTrailPage
	 *
	 * @return string
	 */
	public function addIcon($row, $label, DataContainer $dc = null, $imageAttribute = '', $blnReturnImage = false, $blnProtected = false, $isVisibleRootTrailPage = false) {

		return $label;

	}

}
