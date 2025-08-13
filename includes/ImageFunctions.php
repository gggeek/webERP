<?php

function GetImageLink($ImageFile, $Item, $Width, $Height, $AltText, $Class = "") {
    if (extension_loaded('gd')
		and function_exists('gd_info')
		and file_exists($ImageFile)
		and isset($Item)
		and !empty($Item)) {
        if ($_SESSION['ShowStockidOnImages'] == '0'){
			$ImageLink = '<img class="StockImage" src="GetStockImage.php?automake=1' .
                                                    '&textcolor=FFFFFF' .
                                                    '&bgcolor=000000' .
                                                    '&width=' . $Width .
                                                    '&height=' . $Height .
                                                    '&StockID=' . urlencode($Item) .
													'" alt="' . htmlspecialchars($AltText, ENT_QUOTES) . '" />';
        } else {
            $ImageLink = '<img class="StockImage" src="GetStockImage.php?automake=1' .
                                                    '&textcolor=FFFFFF' .
                                                    '&bgcolor=000000' .
                                                    '&text=' . $Item .
                                                    '&width=' . $Width .
                                                    '&height=' . $Height .
                                                    '&StockID=' . urlencode($Item) .
													'" alt="' . htmlspecialchars($AltText, ENT_QUOTES) . '" />';
		}
    } else if (file_exists($ImageFile)) {
        $ImageLink = '<img class="StockImage" src="' . $ImageFile .
                                            '" width="' . $Width .
                                            '" height="' . $Height . '" />';
    } else {
        $ImageLink = __('No Image');
    }
   return $ImageLink;
}
