<?php
class Jetpack_HeroCallout_Block_Widget_Wysiwyg extends Mage_Adminhtml_Block_Widget_Form_Renderer_Fieldset_Element {
    public function render(Varien_Data_Form_Element_Abstract $element) {
        $editor = new Varien_Data_Form_Element_Editor($element->getData());
        $editor->setId($element->getId());
        $editor->setForm($element->getForm());
        $editor->setWysiwyg(true);
        $editor->setForceLoad(false);
        return parent::render($editor);
    }
}
?>