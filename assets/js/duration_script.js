
let rangeSlider_min = 1;
let rangeSlider_max = 30;

// Get references to elements
const slider = document.querySelector('#RangeSlider');
const leftInput = slider.querySelector('.range-slider-input-left');
const rightInput = slider.querySelector('.range-slider-input-right');
const minNumberInput = document.querySelector('#minValue');
const maxNumberInput = document.querySelector('#maxValue');
const rangeDisplay = document.querySelector('#rangeDisplay');

// Initialize slider
function initializeSlider() {
    // Read values from PHP-populated inputs or use defaults
    const minFromInput = parseInt(minNumberInput.value) || 1;
    const maxFromInput = parseInt(maxNumberInput.value) || 30;
    
    // Validate and set the range values
    rangeSlider_min = Math.max(1, Math.min(minFromInput, 29)); // Ensure min is at least 1 and less than max
    rangeSlider_max = Math.max(rangeSlider_min + 1, Math.min(maxFromInput, 30)); // Ensure max is greater than min and at most 30
    
    // Set the slider input ranges
    leftInput.min = 1;
    leftInput.max = 30;
    rightInput.min = 1;
    rightInput.max = 30;
    
    // Set number input ranges
    minNumberInput.min = 1;
    minNumberInput.max = 30;
    maxNumberInput.min = 1;
    maxNumberInput.max = 30;
    
    updateSliderVisuals();
    updateNumberInputs();
    if (rangeDisplay) {
        updateRangeDisplay();
    }
}

function updateSliderVisuals() {
    const leftVal = document.querySelector('#RangeSlider .range-slider-val-left');
    const rightVal = document.querySelector('#RangeSlider .range-slider-val-right');
    const rangeVal = document.querySelector('#RangeSlider .range-slider-val-range');
    const leftHandle = document.querySelector('#RangeSlider .range-slider-handle-left');
    const rightHandle = document.querySelector('#RangeSlider .range-slider-handle-right');
    const leftTooltip = document.querySelector('#RangeSlider .range-slider-tooltip-left');
    const rightTooltip = document.querySelector('#RangeSlider .range-slider-tooltip-right');
    const leftTooltipText = leftTooltip.querySelector('.range-slider-tooltip-text');
    const rightTooltipText = rightTooltip.querySelector('.range-slider-tooltip-text');

    // Calculate percentages based on 1-30 range
    const minPercentage = ((rangeSlider_min - 1) / (30 - 1)) * 100;
    const maxPercentage = ((rangeSlider_max - 1) / (30 - 1)) * 100;

    leftVal.style.width = `${minPercentage}%`;
    rightVal.style.width = `${100 - maxPercentage}%`;
    rangeVal.style.left = `${minPercentage}%`;
    rangeVal.style.right = `${100 - maxPercentage}%`;
    leftHandle.style.left = `${minPercentage}%`;
    rightHandle.style.left = `${maxPercentage}%`;
    leftTooltip.style.left = `${minPercentage}%`;
    rightTooltip.style.left = `${maxPercentage}%`;
    leftTooltipText.innerText = rangeSlider_min;
    rightTooltipText.innerText = rangeSlider_max;
    leftInput.value = rangeSlider_min;
    rightInput.value = rangeSlider_max;
}

function updateNumberInputs() {
    minNumberInput.value = rangeSlider_min;
    maxNumberInput.value = rangeSlider_max;
}

function updateRangeDisplay() {
    rangeDisplay.textContent = `${rangeSlider_min} - ${rangeSlider_max}`;
}

// Left slider event listener
leftInput.addEventListener('input', function(e) {
    const newValue = Math.min(parseInt(e.target.value), rangeSlider_max - 1);
    rangeSlider_min = newValue;
    e.target.value = newValue;
    
    const value = ((parseInt(e.target.value) - 1) / (30 - 1)) * 100;
    const children = e.target.parentNode.childNodes[1].childNodes;
    
    children[1].style.width = `${value}%`;
    children[5].style.left = `${value}%`;
    children[7].style.left = `${value}%`;
    children[11].style.left = `${value}%`;
    children[11].childNodes[1].innerHTML = e.target.value;
    
    updateNumberInputs();
    if (rangeDisplay) {
        updateRangeDisplay();
    }
});

// Right slider event listener
rightInput.addEventListener('input', function(e) {
    const newValue = Math.max(parseInt(e.target.value), rangeSlider_min + 1);
    rangeSlider_max = newValue;
    e.target.value = newValue;
    
    const value = ((parseInt(e.target.value) - 1) / (30 - 1)) * 100;
    const children = e.target.parentNode.childNodes[1].childNodes;
    
    children[3].style.width = `${100 - value}%`;
    children[5].style.right = `${100 - value}%`;
    children[9].style.left = `${value}%`;
    children[13].style.left = `${value}%`;
    children[13].childNodes[1].innerHTML = e.target.value;
    
    updateNumberInputs();
    if (rangeDisplay) {
        updateRangeDisplay();
    }
});

// Number input event listeners
minNumberInput.addEventListener('input', function(e) {
    let newValue = parseInt(e.target.value);
    if (isNaN(newValue) || newValue < 1) {
        newValue = 1;
        e.target.value = newValue;
    }
    if (newValue >= rangeSlider_max) {
        newValue = rangeSlider_max - 1;
        e.target.value = newValue;
    }
    
    rangeSlider_min = newValue;
    updateSliderVisuals();
    if (rangeDisplay) {
        updateRangeDisplay();
    }
});

maxNumberInput.addEventListener('input', function(e) {
    let newValue = parseInt(e.target.value);
    if (isNaN(newValue) || newValue > 30) {
        newValue = 30;
        e.target.value = newValue;
    }
    if (newValue <= rangeSlider_min) {
        newValue = rangeSlider_min + 1;
        e.target.value = newValue;
    }
    
    rangeSlider_max = newValue;
    updateSliderVisuals();
    if (rangeDisplay) {
        updateRangeDisplay();
    }
});

// Initialize the slider when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeSlider();
});

// Also initialize immediately if DOM is already loaded (for WordPress compatibility)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSlider);
} else {
    initializeSlider();
}