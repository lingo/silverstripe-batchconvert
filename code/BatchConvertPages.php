<?php

/**
 * A batch action to convert many pages to a new type.
 */
class BatchConvertPages extends CMSBatchAction {

	function getActionTitle() {
		return _t('BatchConvertPages.TITLE', 'Batch convert page types');
	}

	function getDoingText() {
		return _t('BatchConvertPages.DOING', 'Converting pages');
	}

	function run(DataObjectSet $pages) {
		$control = Controller::curr();
		$request = $control->getRequest();
		$type = $request->postVar('PageType');
		$done = array();
		if (!class_exists($type)) {
			FormResponse::error(_t('BatchConvertPages.INVALIDPAGETYPE', 'Invalid Page Type'));
		} else {
			foreach($pages as $page) {
				$page->class = $type;
				$page->ClassName = $type;
				$page->write();
				$done[] = $page->Title;
			}
			FormResponse::status_message('Converted ' . count($done) . ' pages to type ' . $type, 'good');
			FormResponse::status_message(
					sprintf(
						_t('BatchConvertPages.CONVERTEDPAGES', 'Converted %s pages to type %s'),
						count($done),
						$type
					       ));
		}
		return FormResponse::respond();
	}

	function getParameterFields() {
		return new FieldSet(
			new DropdownField("PageType", 'Page type', $this->getClassDropdown())
		);
	}

	function changepagetypes() {
	}

	/**
	 * Get the class dropdown used in the CMS to change the class of a page.
	 * This returns the list of options in the drop as a Map from class name
	 * to text in dropdown.
	 *
	 * @return array
	 */
	protected function getClassDropdown() {
		$classes = SiteTree::page_type_classes();
		
		$result = array();
		foreach($classes as $class) {
			$instance = singleton($class);
			if((($instance instanceof HiddenClass) || !$instance->canCreate())) continue;

			$pageTypeName = $instance->i18n_singular_name();

			$translation = _t(
				'SiteTree.CHANGETO', 
				'Change to "%s"', 
				PR_MEDIUM,
				"Pagetype selection dropdown with class names"
			);

			// @todo legacy fix to avoid empty classname dropdowns when translation doesn't include %s
			if(strpos($translation, '%s') !== FALSE) {
				$result[$class] = sprintf(
					$translation, 
					$pageTypeName
				);
			} else {
				$result[$class] = "{$translation} \"{$pageTypeName}\"";
			}

			// if we're in translation mode, the link between the translated pagetype
			// title and the actual classname might not be obvious, so we add it in parantheses
			// Example: class "RedirectorPage" has the title "Weiterleitung" in German,
			// so it shows up as "Weiterleitung (RedirectorPage)"
			if(i18n::get_locale() != 'en_US') {
				$result[$class] = $result[$class] .  " ({$class})";
			}
		}
		
		// sort alphabetically, and put current on top
		asort($result);
		
		return $result;
	}

}
