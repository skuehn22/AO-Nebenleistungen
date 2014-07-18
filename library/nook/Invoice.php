<?php
class nook_Invoice extends Zend_Pdf{
	
	private $_pdf;
	private $_page;
	private $_fileName;
	
	private $_styleText;
	private $_styleHeadline;
	private $_styleLine;
	private $_styleLabel;
	private $_meassureStyleLine;
	private $_heightBlock = 0;
	private $_textSpacing;
	private $_textHeight;
	
	private $_leftSmall = 70;
	private $_leftLarge = 180;
	
	private function _setTextStyle(){
		$this->_textHeight = 10;
        $styleText = new Zend_Pdf_Style();
        $styleText->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), $this->_textHeight);
        $styleText->setFillColor(new Zend_Pdf_Color_Html('black'));
        $this->_styleText = $styleText;
        
        return;
	}
	
	public function _setHeadLineStyle(){
        $styleHeadline = new Zend_Pdf_Style();
        $styleHeadline->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 20);
        $styleHeadline->setFillColor(new Zend_Pdf_Color_Html('black'));
        $this->_styleHeadline = $styleHeadline;
        
        return;
	}
	
	private function _setImageStayle(){
		
		
	}
	
	private function _setLineStyle(){
		$styleLine = new Zend_Pdf_Style();
        $styleLine->setLineDashingPattern(Zend_Pdf_Page::LINE_DASHING_SOLID);
        $styleLine->setLineColor(new Zend_Pdf_Color_GrayScale(0));
        $styleLine->setLineWidth(0.2);
		$this->_styleLine = $styleLine;
        
		return;
	}
	
	private function _setLabelStyle(){
		$styleLabel = new Zend_Pdf_Style();
        $styleLabel->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 12);
        $styleLabel->setFillColor(new Zend_Pdf_Color_Html('black'));
        $this->_styleLabel = $styleLabel;
		
        return;
	}
	
	public function setLogo($__logoNameWithPath, $__left, $__bottom, $__right, $__top){
		// $page->drawImage($image, 100, 696, 500, 750);
		$image = Zend_Pdf_Image::imageWithPath($__logoNameWithPath);
        $this->_page->drawImage($image, $__left, $__bottom, $__right, $__top);
		
        
        $this->_heightBlock = 0;
		return;
	}
	
	public function setTextBlock($__text, $__bottom, $__spacing){
		$this->_textSpacing = $__spacing;
		
		$this->_calculateHeightBlock($__text);
		$__bottom = $__bottom - $this->_heightBlock;
		
		$text = explode("\n", $__text);
		$count = count($text);
		$count--;
		$this->_page->setStyle($this->_styleText);
		
		$j = -1;
		for($i=$count; $i>-1; $i--){
			$j++;
			$bottom = $__bottom + ($j * $this->_textSpacing);
	        $this->_page->drawText($text[$i], $this->_leftLarge, $bottom, 'UTF-8');
		}
		
		$this->_heightBlock = 0;
        return;
	}
	
	private function _calculateHeightBlock($__text){
		$teile = explode("\n", $__text);
		$count = count($teile);
		$heightBlock = $count * $this->_textHeight;
		$this->_heightBlock = $heightBlock;
		
		return;
	}
	
	public function setText($__text, $__bottom){
		$this->_page->setStyle($this->_styleText);
        $this->_page->drawText($__text, $this->_leftLarge, $__bottom, 'UTF-8');
        
        return;
	}
	
	public function setLabel($__text, $__bottom){
		$this->_page->setStyle($this->_styleLabel);
        $this->_page->drawText($__text, $this->_leftSmall, $__bottom, 'UTF-8');
        
        return;
	}
	
	public function setImageBlock($__image, $__left, $__bottom){
		
		
		$this->_heightBlock = 0;
		return;
	}
	
	public function setHeadLine($__headline, $__bottom){
		$this->_page->setStyle($this->_styleHeadline);
        $this->_page->drawText($__headline, $this->_leftSmall, $__bottom, 'UTF-8');
		
	}
	
	public function start($__fileName, $meassure = false){
		$this->_fileName = $__fileName;
		$this->_pdf = Zend_Pdf::load($__fileName);
		$this->_page = $this->_pdf->pages[0];
		
		$this->_setTextStyle();
		$this->_setHeadLineStyle();
		$this->_setLineStyle();
		$this->_setLabelStyle();
		$this->_setMeassureLineStyle();
		
		if(!empty($meassure))
			$this->_drawRaster();
		
		return;
	}
	
	private function _drawRaster(){
		$this->_page->setStyle($this->_meassureStyleLine);
		$this->_page->setStyle($this->_styleText);
		
		for($i=1; $i<40; $i++){
			$hoch = $i * 20;
			$hoch_text = $hoch + 2;
			$this->_page->drawText('Höhe: '.$hoch, 10, $hoch_text, 'UTF-8');
			$this->_page->drawLine(10, $hoch, 550, $hoch);
		}
		
		$this->_page->drawLine($this->_leftSmall, 20, $this->_leftSmall, 800);
		$this->_page->drawLine($this->_leftLarge, 20, $this->_leftLarge, 800);
		
		
		return;
	}
	
	private function _setMeassureLineStyle(){
		$meassureStyleLine = new Zend_Pdf_Style();
        $meassureStyleLine->setLineDashingPattern(Zend_Pdf_Page::LINE_DASHING_SOLID);
        $meassureStyleLine->setLineColor(new Zend_Pdf_Color_Html('blue'));
        $meassureStyleLine->setLineWidth(0.2);
		$this->_meassureStyleLine = $meassureStyleLine;
		
		return;
	}
		
	public function drawInvoice($__invoiceFile){
		
		$control = $this->_pdf->save($__invoiceFile);
		
		return $control;
	}
	
	
	
	
	
}