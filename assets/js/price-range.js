
// Complete Price Range JavaScript for WordPress - FIXED
(function() {
    // Get URL parameters function
    function getUrlParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }

    // Initialize values from URL or defaults
    const initialMinPrice = parseInt(getUrlParameter('price_min')) || 100;
    const initialMaxPrice = parseInt(getUrlParameter('price_max')) || 5000;

    // Get DOM elements
    const minRange = document.getElementById('minRange');
    const maxRange = document.getElementById('maxRange');
    const minPriceInput = document.getElementById('minPriceInput');
    const maxPriceInput = document.getElementById('maxPriceInput');
    const rangeTrack = document.getElementById('rangeTrack');
    const currentRange = document.getElementById('currentRange');

    // Check if all elements exist before proceeding
    if (!minRange || !maxRange || !minPriceInput || !maxPriceInput || !rangeTrack) {
        console.error('Price range elements not found');
        return;
    }

    function updateRangeFromSlider() {
        let minVal = parseInt(minRange.value);
        let maxVal = parseInt(maxRange.value);
        
        // Ensure min doesn't exceed max
        if (minVal >= maxVal) {
            minVal = maxVal - 100;
            minRange.value = minVal;
        }
        
        // Ensure max doesn't go below min
        if (maxVal <= minVal) {
            maxVal = minVal + 100;
            maxRange.value = maxVal;
        }
        
        // Update input fields
        minPriceInput.value = minVal;
        maxPriceInput.value = maxVal;
        
        updateVisualTrack(minVal, maxVal);
        updateCurrentRange(minVal, maxVal);
    }

    // This function now only validates and updates on blur/change, not during typing
    function validateAndUpdateFromInput() {
        let minVal = parseInt(minPriceInput.value) || 100;
        let maxVal = parseInt(maxPriceInput.value) || 5000;
        
        // Validate bounds
        minVal = Math.max(100, Math.min(minVal, 5000));
        maxVal = Math.max(100, Math.min(maxVal, 5000));
        
        // Ensure min doesn't exceed max
        if (minVal >= maxVal) {
            minVal = maxVal - 100;
            if (minVal < 100) {
                minVal = 100;
                maxVal = 200;
            }
        }
        
        // Update sliders and inputs only if values changed
        if (parseInt(minRange.value) !== minVal) {
            minRange.value = minVal;
            minPriceInput.value = minVal;
        }
        if (parseInt(maxRange.value) !== maxVal) {
            maxRange.value = maxVal;
            maxPriceInput.value = maxVal;
        }
        
        updateVisualTrack(minVal, maxVal);
        updateCurrentRange(minVal, maxVal);
    }

    // New function to update visual elements while user is typing (without validation)
    function updateVisualsFromInput() {
        const minVal = parseInt(minPriceInput.value);
        const maxVal = parseInt(maxPriceInput.value);
        
        // Only update if values are numbers and within reasonable bounds
        if (!isNaN(minVal) && !isNaN(maxVal) && minVal >= 100 && maxVal <= 5000 && minVal < maxVal) {
            // Update slider positions immediately
            minRange.value = minVal;
            maxRange.value = maxVal;
            
            // Update visual track and display
            updateVisualTrack(minVal, maxVal);
            updateCurrentRange(minVal, maxVal);
        }
    }

    function updateVisualTrack(minVal, maxVal) {
        const minPercent = ((minVal - 100) / (5000 - 100)) * 100;
        const maxPercent = ((maxVal - 100) / (5000 - 100)) * 100;
        
        rangeTrack.style.left = minPercent + '%';
        rangeTrack.style.width = (maxPercent - minPercent) + '%';
    }

    function updateCurrentRange(minVal, maxVal) {
        if (currentRange) {
            currentRange.textContent = `${minVal} SEK - ${maxVal} SEK`;
        }
    }

    // Set initial values from URL parameters
    function initializeFromUrl() {
        // Validate initial values
        const validMinPrice = Math.max(100, Math.min(initialMinPrice, 5000));
        const validMaxPrice = Math.max(100, Math.min(initialMaxPrice, 5000));
        
        // Ensure min doesn't exceed max
        const finalMinPrice = validMinPrice >= validMaxPrice ? validMaxPrice - 100 : validMinPrice;
        const finalMaxPrice = validMaxPrice <= finalMinPrice ? finalMinPrice + 100 : validMaxPrice;
        
        // Set all elements
        minRange.value = finalMinPrice;
        maxRange.value = finalMaxPrice;
        minPriceInput.value = finalMinPrice;
        maxPriceInput.value = finalMaxPrice;
        
        updateVisualTrack(finalMinPrice, finalMaxPrice);
        updateCurrentRange(finalMinPrice, finalMaxPrice);
    }

    // Event listeners - FIXED
    minRange.addEventListener('input', updateRangeFromSlider);
    maxRange.addEventListener('input', updateRangeFromSlider);
    
    // Allow typing in input fields - only update visuals during typing
    minPriceInput.addEventListener('input', updateVisualsFromInput);
    maxPriceInput.addEventListener('input', updateVisualsFromInput);
    
    // Validate and correct values when user finishes typing
    minPriceInput.addEventListener('blur', validateAndUpdateFromInput);
    maxPriceInput.addEventListener('blur', validateAndUpdateFromInput);
    minPriceInput.addEventListener('change', validateAndUpdateFromInput);
    maxPriceInput.addEventListener('change', validateAndUpdateFromInput);

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeFromUrl);
    } else {
        initializeFromUrl();
    }

})();