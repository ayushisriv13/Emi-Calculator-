<?php
// Project: Advanced Loan EMI Dashboard Engine
// Framework: Core PHP / Vanilla JavaScript / Chart.js Data Visualization

// Read setup values from URL variables if they exist
$loan_amount   = isset($_GET['p']) ? floatval($_GET['p']) : (isset($_POST['principal']) ? floatval($_POST['principal']) : 5000000);
$interest_rate = isset($_GET['r']) ? floatval($_GET['r']) : (isset($_POST['rate']) ? floatval($_POST['rate']) : 9.0);
$duration_type = isset($_GET['m']) ? $_GET['m'] : (isset($_POST['tenure_type']) ? $_POST['tenure_type'] : 'years');
$duration_val  = isset($_GET['t']) ? intval($_GET['t']) : (isset($_POST['tenure']) ? intval($_POST['tenure']) : 20);
$active_profile = isset($_GET['lt']) ? $_GET['lt'] : 'home'; 

$start_year = 2026; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Loan EMI Calculator - Interactive Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="style.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>

<div class="app-wrapper">
  
  <div class="utility-box no-print">
    <div class="utility-title">Dashboard Actions</div>
    <div class="action-btn-container">
      <button type="button" class="theme-toggle-btn" id="modeBtn" onclick="toggleInterfaceTheme()">
        <span id="modeIcon">🌙</span> <span id="modeText">Dark Mode</span>
      </button>
      <button type="button" class="export-btn pdf-btn" onclick="downloadPDFReport()">
        Save PDF Report
      </button>
      <button type="button" class="export-btn excel-btn" onclick="downloadExcelData()">
        Export to Excel
      </button>
      <button type="button" class="export-btn share-btn" onclick="copyDashboardLink()">
        <span>🔗</span> Share Configuration
      </button>
    </div>
  </div>
  
  <div class="content-card">
    <form method="POST" action="" id="emiForm" onsubmit="event.preventDefault(); runMainCalculatorEngine();">
      <div class="dashboard-grid">
        
        <div class="control-side no-print">
          <div class="side-header">
            <h2>Loan Parameters</h2>
          </div>

          <div class="product-selector-box">
            <label class="field-micro-label">Select Loan Type</label>
            <div class="tab-button-group">
              <button type="button" class="tab-btn <?= $active_profile==='home'?'active':'' ?>" id="tab_home" onclick="changeLoanType('home')"> Home</button>
              <button type="button" class="tab-btn <?= $active_profile==='car'?'active':'' ?>" id="tab_car" onclick="changeLoanType('car')"> Car</button>
              <button type="button" class="tab-btn <?= $active_profile==='personal'?'active':'' ?>" id="tab_personal" onclick="changeLoanType('personal')"> Personal</button>
            </div>
          </div>

          <div class="slider-input-group">
            <div class="input-label-row">
              <label id="mainAmountLabel">Home Loan Amount</label>
              <div class="numerical-input-box">
                <span class="currency-symbol">₹</span>
                <input type="number" id="num_principal" value="<?= $loan_amount ?>" min="100000" max="20000000" step="50000"/>
              </div>
            </div>
            <input type="range" class="html-range-slider" id="range_principal" min="100000" max="20000000" step="50000" value="<?= $loan_amount ?>"/>
            <div class="quick-preset-row" id="principalPresets"></div>
          </div>

          <div class="slider-input-group">
            <div class="input-label-row">
              <label>Interest Rate</label>
              <div class="numerical-input-box">
                <input type="number" id="num_rate" value="<?= $interest_rate ?>" min="1" max="25" step="0.05"/>
                <span class="unit-symbol">%</span>
              </div>
            </div>
            <input type="range" class="html-range-slider" id="range_rate" min="1" max="25" step="0.05" value="<?= $interest_rate ?>"/>
            <div class="quick-preset-row" id="ratePresets"></div>
          </div>

          <div class="slider-input-group">
            <div class="input-label-row">
              <label>Loan Tenure</label>
              <div class="numerical-input-box tenure-input-wrapper">
                <input type="number" id="num_tenure" value="<?= $duration_val ?>" min="1" max="360"/>
                <input type="hidden" id="hidden_tenure_type" value="<?= $duration_type ?>"/>
                <div class="unit-toggle-switch">
                  <button type="button" class="unit-switch-btn" id="toggle_yr" onclick="changeTenureUnit('years')">Yr</button>
                  <button type="button" class="unit-switch-btn" id="toggle_mo" onclick="changeTenureUnit('months')">Mo</button>
                </div>
              </div>
            </div>
            <input type="range" class="html-range-slider" id="range_tenure" value="<?= $duration_val ?>"/>
            <div class="quick-preset-row" id="tenurePresets"></div>
          </div>
        </div>

        <div class="display-side">
          <div class="calculated-metrics-summary">
            <div class="summary-metric-card emi-highlight-box">
              <div class="summary-label">Monthly Loan EMI</div>
              <div class="summary-value large-text" id="out_emi">₹0</div>
            </div>
            <div class="split-metric-row">
              <div class="summary-metric-card basic-sub-box p-border">
                <div class="summary-label">Total Principal</div>
                <div class="summary-value medium-text" id="out_principal">₹0</div>
              </div>
              <div class="summary-metric-card basic-sub-box i-border">
                <div class="summary-label">Total Interest</div>
                <div class="summary-value medium-text" id="out_interest">₹0</div>
              </div>
            </div>
          </div>

          <div class="smart-insight-banner no-print" id="insightBox">
            <span class="insight-icon">💡</span>
            <span id="insightText">Processing calculations...</span>
          </div>

          <div class="donut-chart-box">
            <div class="canvas-wrapper">
              <canvas id="loanDonutChart" width="130" height="130"></canvas>
            </div>
            <div class="donut-custom-legend">
              <div class="legend-item-line">
                <span class="color-dot p-dot-color"></span>
                <span class="legend-txt-item">Principal Base Owed (<b id="ratio_p">0%</b>)</span>
              </div>
              <div class="legend-item-line">
                <span class="color-dot i-dot-color"></span>
                <span class="legend-txt-item">Interest Overhead Burden (<b id="ratio_i">0%</b>)</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </form>
  </div>

  <div class="content-card">
    <div class="chart-header-panel">
      <div class="chart-title-heading">Annual Payment Breakdown Timeline:</div>
      <div class="dropdown-filter-group no-print">
        <select class="form-dropdown-select" id="startMonthDropdown" onchange="runMainCalculatorEngine()">
          <option value="0">Jan</option><option value="1">Feb</option><option value="2">Mar</option>
          <option value="3">Apr</option><option value="4">May</option><option value="5" selected>Jun</option>
          <option value="6">Jul</option><option value="7">Aug</option><option value="8">Sep</option>
          <option value="9">Oct</option><option value="10">Nov</option><option value="11">Dec</option>
        </select>
        <select class="form-dropdown-select" id="startYearDropdown" onchange="runMainCalculatorEngine()">
          <option value="2026" selected>2026</option>
          <option value="2027">2027</option>
          <option value="2028">2028</option>
          <option value="2029">2029</option>
          <option value="2030">2030</option>
        </select>
      </div>
    </div>
    <div class="timeline-chart-frame">
      <canvas id="paymentTimelineChart"></canvas>
    </div>
  </div>

  <div class="content-card">
    <table class="data-amort-table">
      <thead>
        <tr>
          <th style="text-align: left; padding-left: 20px;">Timeline Frame</th>
          <th class="th-green">Principal (A)</th>
          <th class="th-orange">Interest (B)</th>
          <th class="th-gray">Total Paid (A + B)</th>
          <th class="th-red">Remaining Balance</th>
          <th>Paid To Date</th>
        </tr>
      </thead>
      <tbody id="tableDataRows"></tbody>
    </table>
  </div>

</div>

<script>
let localTenureUnit = "<?= $duration_type ?>";
let activeProductKey = "<?= $active_profile ?>";
let donutChartObject = null;
let timelineChartObject = null;
let loanTableData = {}; 

const monthNamesList = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

const loanProductProfiles = {
    home: {
        label: "Home Loan Amount",
        minAmt: 500000, maxAmt: 20000000, stepAmt: 50000, defaultAmt: 5000000,
        defaultRate: 8.75, maxYears: 30,
        presetsAmt: [ {lbl:'25L', val:2500000}, {lbl:'50L', val:5000000}, {lbl:'1 Cr', val:10000000} ],
        presetsRate: [7.5, 8.5, 9.5]
    },
    car: {
        label: "Car Loan Amount",
        minAmt: 100000, maxAmt: 5000000, stepAmt: 25000, defaultAmt: 1200000,
        defaultRate: 9.50, maxYears: 7,
        presetsAmt: [ {lbl:'5L', val:500000}, {lbl:'12L', val:1200000}, {lbl:'25L', val:2500000} ],
        presetsRate: [8.5, 9.5, 11.0]
    },
    personal: {
        label: "Personal Loan Amount",
        minAmt: 50000, maxAmt: 2500000, stepAmt: 10000, defaultAmt: 400000,
        defaultRate: 12.50, maxYears: 5,
        presetsAmt: [ {lbl:'1L', val:100000}, {lbl:'4L', val:400000}, {lbl:'10L', val:1000000} ],
        presetsRate: [10.5, 12.5, 15.0]
    }
};

function formatDisplayCurrency(num) {
    return '₹' + Math.round(num).toLocaleString('en-IN');
}

function adjustPrincipal(val) {
    document.getElementById('num_principal').value = val;
    document.getElementById('range_principal').value = val;
    syncSliderTrackColor(document.getElementById('range_principal'));
    runMainCalculatorEngine();
}

function adjustRate(val) {
    document.getElementById('num_rate').value = val;
    document.getElementById('range_rate').value = val;
    syncSliderTrackColor(document.getElementById('range_rate'));
    runMainCalculatorEngine();
}

function changeLoanType(productKey) {
    activeProductKey = productKey;
    
    document.getElementById('tab_home').classList.toggle('active', productKey === 'home');
    document.getElementById('tab_car').classList.toggle('active', productKey === 'car');
    document.getElementById('tab_personal').classList.toggle('active', productKey === 'personal');

    const config = loanProductProfiles[productKey];
    document.getElementById('mainAmountLabel').textContent = config.label;
    
    const sliderP = document.getElementById('range_principal');
    const inputP = document.getElementById('num_principal');
    sliderP.min = inputP.min = config.minAmt;
    sliderP.max = inputP.max = config.maxAmt;
    sliderP.step = inputP.step = config.stepAmt;
    sliderP.value = inputP.value = config.defaultAmt;

    let amtHtml = '';
    config.presetsAmt.forEach(item => {
        amtHtml += `<button type="button" class="preset-pill-btn" onclick="adjustPrincipal(${item.val})">${item.lbl}</button>`;
    });
    document.getElementById('principalPresets').innerHTML = amtHtml;

    let rateHtml = '';
    config.presetsRate.forEach(pct => {
        rateHtml += `<button type="button" class="preset-pill-btn" onclick="adjustRate(${pct})">${pct}%</button>`;
    });
    document.getElementById('ratePresets').innerHTML = rateHtml;

    adjustRate(config.defaultRate);
    changeTenureUnit(localTenureUnit); 
    adjustTenure(localTenureUnit === 'years' ? config.maxYears : config.maxYears * 12);
}

function adjustTenure(val) {
    document.getElementById('num_tenure').value = val;
    document.getElementById('range_tenure').value = val;
    syncSliderTrackColor(document.getElementById('range_tenure'));
    runMainCalculatorEngine();
}

function downloadPDFReport() { window.print(); }

function downloadExcelData() {
    if(Object.keys(loanTableData).length === 0) return;
    
    let csvDataString = "data:text/csv;charset=utf-8,Timeline Frame,Principal Paid (A),Interest Component (B),Total Service Payment,Remaining Balance Owed,Loan Paid To Date (%)\r\n";
    Object.keys(loanTableData).forEach(yearKey => {
        let annualBlock = loanTableData[yearKey];
        csvDataString += `Year ${yearKey},${Math.round(annualBlock.principal)},${Math.round(annualBlock.interest)},${Math.round(annualBlock.principal + annualBlock.interest)},${Math.round(annualBlock.balance)},${annualBlock.percentagePaid}\r\n`;
        annualBlock.months.forEach(m => {
            csvDataString += `${m.monthLabel} ${yearKey},${Math.round(m.principalComponent)},${Math.round(m.interestComponent)},${Math.round(m.principalComponent + m.interestComponent)},${Math.round(m.currentBalance)},${m.percentagePaid}\r\n`;
        });
    });
    
    const encodedUriPath = encodeURI(csvDataString);
    const mockAnchorElement = document.createElement("a");
    mockAnchorElement.setAttribute("href", encodedUriPath);
    mockAnchorElement.setAttribute("download", `${activeProductKey}_loan_repayment_sheet.csv`);
    document.body.appendChild(mockAnchorElement);
    mockAnchorElement.click();
    document.body.removeChild(mockAnchorElement);
}

function copyDashboardLink() {
    const p = document.getElementById('num_principal').value;
    const r = document.getElementById('num_rate').value;
    const t = document.getElementById('num_tenure').value;
    const m = localTenureUnit;
    const lt = activeProductKey;
    const customShareUrl = window.location.origin + window.location.pathname + `?p=${p}&r=${r}&t=${t}&m=${m}&lt=${lt}`;
    navigator.clipboard.writeText(customShareUrl).then(() => {
        alert("✨ Shareable dashboard link copied to clipboard successfully!");
    }).catch(() => { alert("Configuration share link query: " + customShareUrl); });
}

function runMainCalculatorEngine() {
    const principalAmount = parseFloat(document.getElementById('num_principal').value) || 0;
    const interestPercentage = parseFloat(document.getElementById('num_rate').value) || 0;
    const durationInputValue = parseInt(document.getElementById('num_tenure').value) || 0;
    
    let totalMonthsCount = (localTenureUnit === 'years') ? durationInputValue * 12 : durationInputValue;
    let monthlyInterestFraction = interestPercentage / 12 / 100;
    
    let calculatedEmi = 0;
    if (monthlyInterestFraction > 0 && totalMonthsCount > 0) {
        calculatedEmi = (principalAmount * monthlyInterestFraction * Math.pow(1 + monthlyInterestFraction, totalMonthsCount)) / (Math.pow(1 + monthlyInterestFraction, totalMonthsCount) - 1);
    } else {
        calculatedEmi = (principalAmount / (totalMonthsCount || 1));
    }
    calculatedEmi = Math.round(calculatedEmi * 100) / 100;
    
    let overallCumulativePayout = Math.round(calculatedEmi * totalMonthsCount * 100) / 100;
    let absoluteInterestBurden = Math.round((overallCumulativePayout - principalAmount) * 100) / 100;

    document.getElementById('out_emi').textContent = formatDisplayCurrency(calculatedEmi);
    document.getElementById('out_principal').textContent = formatDisplayCurrency(principalAmount);
    document.getElementById('out_interest').textContent = formatDisplayCurrency(absoluteInterestBurden);

    let principalRatio = overallCumulativePayout > 0 ? ((principalAmount / overallCumulativePayout) * 100).toFixed(1) : 0;
    let interestRatio = overallCumulativePayout > 0 ? ((absoluteInterestBurden / overallCumulativePayout) * 100).toFixed(1) : 0;
    document.getElementById('ratio_p').textContent = principalRatio + '%';
    document.getElementById('ratio_i').textContent = interestRatio + '%';

    const alertBox = document.getElementById('insightBox');
    const alertText = document.getElementById('insightText');
    if (interestRatio > 50) {
        alertBox.className = "smart-insight-banner red-alert";
        alertText.textContent = "Warning: Interest costs exceed your principal loan amount. Consider decreasing tenure.";
    } else if (interestRatio > 25) {
        alertBox.className = "smart-insight-banner yellow-alert";
        alertText.textContent = "Balanced Ratio: Interest burden is stable. Good for standard structural timelines.";
    } else {
        alertBox.className = "smart-insight-banner green-alert";
        alertText.textContent = "Excellent Setup: Highly efficient tenure selection! Minimal interest payout overhead.";
    }

    renderDonutGraphic(principalAmount, absoluteInterestBurden);
    compileAmortizationDataMatrix(principalAmount, totalMonthsCount, monthlyInterestFraction, calculatedEmi);
}

function renderDonutGraphic(p, i) {
    if (donutChartObject) donutChartObject.destroy();
    const elementCtx = document.getElementById('loanDonutChart');
    if (!elementCtx) return;
    
    donutChartObject = new Chart(elementCtx, {
        type: 'doughnut',
        data: {
            labels: ['Principal Base', 'Interest Cost'],
            datasets: [{ data: [p, i], backgroundColor: ['#00b4d8', '#ffb703'], borderWidth: 2, borderColor: '#ffffff' }]
        },
        options: { responsive: false, cutout: '70%', plugins: { legend: { display: false } } }
    });
}

function compileAmortizationDataMatrix(principal, totalMonths, monthlyFraction, emiAmount) {
    let unservicedBalance = principal;
    let chosenMonthIndex = parseInt(document.getElementById('startMonthDropdown').value);
    let chosenYearValue = parseInt(document.getElementById('startYearDropdown').value);
    
    loanTableData = {};
    let runningMonthlyPrincipalSum = 0;
    let loopMonthPointer = chosenMonthIndex;
    let loopYearPointer = chosenYearValue;

    for (let index = 1; index <= totalMonths; index++) {
        let monthlyInterestAccrued = unservicedBalance * monthlyFraction;
        let monthlyPrincipalCleared = emiAmount - monthlyInterestAccrued;
        
        if (unservicedBalance < monthlyPrincipalCleared) {
            monthlyPrincipalCleared = unservicedBalance;
        }
        
        unservicedBalance -= monthlyPrincipalCleared;
        runningMonthlyPrincipalSum += monthlyPrincipalCleared;
        let monthlyPercentagePaid = Math.min(100, ((runningMonthlyPrincipalSum / principal) * 100)).toFixed(2);

        let computedCalendarYearKey = loopYearPointer;

        if (!loanTableData[computedCalendarYearKey]) {
            loanTableData[computedCalendarYearKey] = { principal: 0, interest: 0, balance: 0, months: [] };
        }
        
        loanTableData[computedCalendarYearKey]['principal'] += monthlyPrincipalCleared;
        loanTableData[computedCalendarYearKey]['interest'] += monthlyInterestAccrued;
        loanTableData[computedCalendarYearKey]['balance'] = Math.max(0, unservicedBalance);
        
        loanTableData[computedCalendarYearKey]['months'].push({
            monthLabel: monthNamesList[loopMonthPointer], 
            principalComponent: monthlyPrincipalCleared, 
            interestComponent: monthlyInterestAccrued, 
            currentBalance: Math.max(0, unservicedBalance),
            percentagePaid: monthlyPercentagePaid
        });

        loopMonthPointer++;
        if (loopMonthPointer > 11) { loopMonthPointer = 0; loopYearPointer++; }
    }

    let accumulatedPrincipal = 0;
    Object.keys(loanTableData).forEach(yearKey => {
        accumulatedPrincipal += loanTableData[yearKey].principal;
        loanTableData[yearKey].percentagePaid = Math.min(100, ((accumulatedPrincipal / principal) * 100)).toFixed(2);
    });

    const htmlTableBody = document.getElementById('tableDataRows');
    if (!htmlTableBody) return; htmlTableBody.innerHTML = "";

    let trackingLabels = []; let datasetPrincipals = []; let datasetInterests = []; let datasetBalances = [];

    Object.keys(loanTableData).forEach((yearKey, indexPosition) => {
        let metricsBlock = loanTableData[yearKey];
        let cumulativeAnnualServiceFee = metricsBlock.principal + metricsBlock.interest;

        trackingLabels.push("Year " + yearKey);
        datasetPrincipals.push(Math.round(metricsBlock.principal));
        datasetInterests.push(Math.round(metricsBlock.interest));
        datasetBalances.push(Math.round(metricsBlock.balance));

        let trParent = document.createElement('tr');
        trParent.className = 'master-row';
        trParent.innerHTML = `
            <td class="toggle-cell"><span class="expand-icon-state">┼</span> Year ${yearKey}</td>
            <td>₹${Math.round(metricsBlock.principal).toLocaleString('en-IN')}</td>
            <td>₹${Math.round(metricsBlock.interest).toLocaleString('en-IN')}</td>
            <td>₹${Math.round(cumulativeAnnualServiceFee).toLocaleString('en-IN')}</td>
            <td>₹${Math.round(metricsBlock.balance).toLocaleString('en-IN')}</td>
            <td><span class="payout-progress-pill">${metricsBlock.percentagePaid}%</span></td>
        `;
        
        let subGroupTargetClass = `sub_row_group_${indexPosition}`;
        trParent.onclick = () => {
            const structuralRows = document.getElementsByClassName(subGroupTargetClass);
            const stateIcon = trParent.querySelector('.expand-icon-state');
            if (structuralRows.length === 0) return;
            const visibilityFlag = structuralRows[0].style.display === 'none';
            for(let i=0; i<structuralRows.length; i++) { structuralRows[i].style.display = visibilityFlag ? 'table-row' : 'none'; }
            stateIcon.textContent = visibilityFlag ? '⊟' : '┼';
        };
        htmlTableBody.appendChild(trParent);

        metricsBlock.months.forEach((mItem) => {
            let trChild = document.createElement('tr');
            trChild.className = `slave-row ${subGroupTargetClass}`;
            trChild.style.display = 'none';
            trChild.innerHTML = `
                <td class="sub-month-text-indent">${mItem.monthLabel}</td>
                <td>₹${Math.round(mItem.principalComponent).toLocaleString('en-IN')}</td>
                <td>₹${Math.round(mItem.interestComponent).toLocaleString('en-IN')}</td>
                <td>₹${Math.round(mItem.principalComponent + mItem.interestComponent).toLocaleString('en-IN')}</td>
                <td>₹${Math.round(mItem.currentBalance).toLocaleString('en-IN')}</td>
                <td><span class="payout-progress-pill">${mItem.percentagePaid}%</span></td>
            `;
            htmlTableBody.appendChild(trChild);
        });
    });

    renderTimelineBarGraph(trackingLabels, datasetPrincipals, datasetInterests, datasetBalances);
}

function renderTimelineBarGraph(xAxisLabels, pDataset, iDataset, bDataset) {
    if (timelineChartObject) timelineChartObject.destroy();
    const renderTargetCtx = document.getElementById('paymentTimelineChart');
    if (!renderTargetCtx) return;

    const canvas2dContext = renderTargetCtx.getContext('2d');
    const visualLineFillGradient = canvas2dContext.createLinearGradient(0, 0, 0, 300);
    visualLineFillGradient.addColorStop(0, 'rgba(230, 57, 70, 0.2)');
    visualLineFillGradient.addColorStop(1, 'rgba(230, 57, 70, 0.0)');

    timelineChartObject = new Chart(canvas2dContext, {
        type: 'bar',
        data: {
            labels: xAxisLabels,
            datasets: [
                { label: 'Principal Component', data: pDataset, backgroundColor: '#00b4d8', borderRadius: 4, stack: 'stackGroupAlpha', order: 2 },
                { label: 'Interest Burden', data: iDataset, backgroundColor: '#ffb703', borderRadius: 4, stack: 'stackGroupAlpha', order: 2 },
                { label: 'Remaining Principal Balance', data: bDataset, type: 'line', borderColor: '#e63946', borderWidth: 3, pointBackgroundColor: '#ffffff', pointBorderColor: '#e63946', pointBorderWidth: 2, pointRadius: 4, fill: true, backgroundColor: visualLineFillGradient, order: 1, yAxisID: 'axisRightY' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, position: 'left' },
                axisRightY: { position: 'right', grid: { display: false } }
            }
        }
    });
}

function changeTenureUnit(modeSetting) {
    localTenureUnit = modeSetting;
    document.getElementById('hidden_tenure_type').value = modeSetting;
    const sliderRef = document.getElementById('range_tenure');
    const inputRef = document.getElementById('num_tenure');
    let numericalVal = parseInt(inputRef.value) || 0;

    document.getElementById('toggle_yr').classList.toggle('active', modeSetting === 'years');
    document.getElementById('toggle_mo').classList.toggle('active', modeSetting === 'months');

    const presetsContainer = document.getElementById('tenurePresets');
    const limitCeiling = loanProductProfiles[activeProductKey].maxYears;

    if (modeSetting === 'years') {
        sliderRef.min = 1; sliderRef.max = limitCeiling; sliderRef.step = 1;
        if (numericalVal > limitCeiling) numericalVal = Math.round(numericalVal / 12);
        sliderRef.value = inputRef.value = Math.max(1, Math.min(limitCeiling, numericalVal));
        
        let division1 = Math.round(limitCeiling / 3);
        let division2 = Math.round((limitCeiling / 3) * 2);
        presetsContainer.innerHTML = `
            <button type="button" class="preset-pill-btn" onclick="adjustTenure(${division1})">${division1} Yrs</button>
            <button type="button" class="preset-pill-btn" onclick="adjustTenure(${division2})">${division2} Yrs</button>
            <button type="button" class="preset-pill-btn" onclick="adjustTenure(${limitCeiling})">${limitCeiling} Yrs</button>
        `;
    } else {
        let maxMonthsCeiling = limitCeiling * 12;
        sliderRef.min = 1; sliderRef.max = maxMonthsCeiling; sliderRef.step = 1;
        if (numericalVal <= limitCeiling) numericalVal = numericalVal * 12;
        sliderRef.value = inputRef.value = Math.max(1, Math.min(maxMonthsCeiling, numericalVal));
        
        let divisionMonths1 = Math.round(maxMonthsCeiling / 3);
        let divisionMonths2 = Math.round((maxMonthsCeiling / 3) * 2);
        presetsContainer.innerHTML = `
            <button type="button" class="preset-pill-btn" onclick="adjustTenure(${divisionMonths1})">${divisionMonths1} Mo</button>
            <button type="button" class="preset-pill-btn" onclick="adjustTenure(${divisionMonths2})">${divisionMonths2} Mo</button>
            <button type="button" class="preset-pill-btn" onclick="adjustTenure(${maxMonthsCeiling})">${maxMonthsCeiling} Mo</button>
        `;
    }
    syncSliderTrackColor(sliderRef);
    runMainCalculatorEngine();
}

function syncSliderTrackColor(sliderElement) {
    const calculationPercentage = ((sliderElement.value - sliderElement.min) / (sliderElement.max - sliderElement.min)) * 100;
    sliderElement.style.background = `linear-gradient(to right, #00b4d8 ${calculationPercentage}%, #e2e8f0 ${calculationPercentage}%)`;
}

function establishInputSyncBinding(sliderSelectorId, rawInputSelectorId) {
    const domSlider = document.getElementById(sliderSelectorId); const domInput = document.getElementById(rawInputSelectorId);
    if(!domSlider || !domInput) return;
    domSlider.addEventListener('input', () => { domInput.value = domSlider.value; syncSliderTrackColor(domSlider); runMainCalculatorEngine(); });
    domInput.addEventListener('input', () => { domSlider.value = domInput.value; syncSliderTrackColor(domSlider); runMainCalculatorEngine(); });
    syncSliderTrackColor(domSlider);
}

function toggleInterfaceTheme() {
    const systemActiveTheme = document.documentElement.getAttribute('data-theme');
    let chosenThemeTarget = (systemActiveTheme !== 'dark') ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', chosenThemeTarget);
    localStorage.setItem('user-selected-theme', chosenThemeTarget);
    applyGlobalChartStyleRules(chosenThemeTarget);
    runMainCalculatorEngine();
}

function applyGlobalChartStyleRules(themeKey) {
    const graphicalIcon = document.getElementById('modeIcon'); const graphicalText = document.getElementById('modeText');
    if(!graphicalIcon || !graphicalText) return;
    if (themeKey === 'dark') {
        graphicalIcon.textContent = '☀️'; graphicalText.textContent = 'Light Mode';
        Chart.defaults.color = '#94a3b8'; Chart.defaults.borderColor = '#334155';
    } else {
        graphicalIcon.textContent = '🌙'; graphicalText.textContent = 'Dark Mode';
        Chart.defaults.color = '#666666'; Chart.defaults.borderColor = '#e2e8f0';
    }
}

window.addEventListener('DOMContentLoaded', () => {
    const fallbackHistoricalTheme = localStorage.getItem('user-selected-theme') || 'light';
    document.documentElement.setAttribute('data-theme', fallbackHistoricalTheme);
    applyGlobalChartStyleRules(fallbackHistoricalTheme);

    changeLoanType(activeProductKey);
    establishInputSyncBinding('range_principal', 'num_principal');
    establishInputSyncBinding('range_rate', 'num_rate');
    establishInputSyncBinding('range_tenure', 'num_tenure');
    runMainCalculatorEngine();
});
</script>
</body>
</html>