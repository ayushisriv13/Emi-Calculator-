<?php
// Project: Advanced Loan EMI Dashboard Engine
// Framework: Core PHP / Vanilla JavaScript / Chart.js

$loan_amount   = isset($_GET['p']) ? floatval($_GET['p']) : 5000000;
$interest_rate = isset($_GET['r']) ? floatval($_GET['r']) : 9.0;
$duration_type = isset($_GET['m']) ? $_GET['m'] : 'years';
$duration_val  = isset($_GET['t']) ? intval($_GET['t']) : 20;
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
    <link rel="stylesheet" href="style.css?v=1.5"/>
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
      <button type="button" class="export-btn pdf-btn" onclick="window.print()">Save PDF Report</button>
      <button type="button" class="export-btn excel-btn" onclick="downloadExcelData()">Export to Excel</button>
      <button type="button" class="export-btn share-btn" onclick="copyDashboardLink()">🔗 Share Setup</button>
    </div>
  </div>
  
  <div class="content-card">
    <form method="POST" id="emiForm" onsubmit="event.preventDefault();">
      <div class="dashboard-grid">
        
        <div class="control-side no-print">
          <div class="side-header"><h2>Loan Parameters</h2></div>

          <div class="product-selector-box">
            <label class="field-micro-label">Select Loan Type</label>
            <div class="tab-button-group">
              <button type="button" class="tab-btn <?= $active_profile==='home'?'active':'' ?>" id="tab_home" onclick="changeLoanType('home')">Home</button>
              <button type="button" class="tab-btn <?= $active_profile==='car'?'active':'' ?>" id="tab_car" onclick="changeLoanType('car')">Car</button>
              <button type="button" class="tab-btn <?= $active_profile==='personal'?'active':'' ?>" id="tab_personal" onclick="changeLoanType('personal')">Personal</button>
            </div>
          </div>

          <div class="slider-input-group">
            <div class="input-label-row">
              <label id="mainAmountLabel">Home Loan Amount</label>
              <div class="numerical-input-box">
                <span class="currency-symbol">₹</span>
                <input type="number" id="num_principal" value="<?= $loan_amount ?>"/>
              </div>
            </div>
            <input type="range" class="html-range-slider" id="range_principal" value="<?= $loan_amount ?>"/>
            <div class="quick-preset-row" id="principalPresets"></div>
          </div>

          <div class="slider-input-group">
            <div class="input-label-row">
              <label>Interest Rate</label>
              <div class="numerical-input-box">
                <input type="number" id="num_rate" value="<?= $interest_rate ?>" step="0.05"/>
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
                <input type="number" id="num_tenure" value="<?= $duration_val ?>"/>
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
            <span class="insight-icon">💡</span> <span id="insightText">Processing calculations...</span>
          </div>

          <div class="donut-chart-box">
            <canvas id="loanDonutChart" width="130" height="130"></canvas>
            <div class="donut-custom-legend">
              <div class="legend-item-line"><span class="color-dot p-dot-color"></span><span>Principal Base (<b id="ratio_p">0%</b>)</span></div>
              <div class="legend-item-line"><span class="color-dot i-dot-color"></span><span>Interest Burden (<b id="ratio_i">0%</b>)</span></div>
            </div>
          </div>
        </div>

      </div>
    </form>
  </div>

  <div class="content-card">
    <div class="chart-header-panel">
      <div class="chart-title-heading">Annual Repayment Timeline Schedule:</div>
      <div class="dropdown-filter-group no-print">
        <div class="month-year-picker-wrapper">
          <div class="picker-input-box" id="pickerInputBox" onclick="toggleMonthYearPopup()">
            <span id="pickerDisplayText">Jun 2026</span> <span>📅</span>
          </div>
          <div class="month-year-popup-panel" id="monthYearPopup">
            <div class="popup-year-nav">
              <button type="button" onclick="shiftPopupYear(-1)">«</button>
              <span id="popupYearLabel">2026</span>
              <button type="button" onclick="shiftPopupYear(1)">»</button>
            </div>
            <div class="popup-month-grid" id="popupMonthGrid"></div>
          </div>
          <input type="hidden" id="startMonthDropdown" value="5"/>
          <input type="hidden" id="startYearInput" value="2026"/>
        </div>
        <select class="form-dropdown-select" id="yearTypeDropdown" onchange="runMainCalculatorEngine()">
          <option value="calendar" selected>Calendar Year wise</option>
          <option value="financial">Financial Year wise</option>
        </select>
      </div>
    </div>
    <div class="timeline-chart-frame"><canvas id="paymentTimelineChart"></canvas></div>
  </div>

  <div class="content-card">
    <table class="data-amort-table">
      <thead>
        <tr>
          <th style="text-align: left; padding-left: 20px;">Timeline Frame</th>
          <th class="th-green">Principal (A)</th>
          <th class="th-orange">Interest (B)</th>
          <th class="th-gray">Total Paid</th>
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
let donutChartObject = null, timelineChartObject = null;
let loanTableData = {}, popupDisplayedYear = 2026;

const monthNamesList = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const loanProductProfiles = {
    home: { label: "Home Loan Amount", minAmt: 500000, maxAmt: 20000000, stepAmt: 50000, defaultAmt: 5000000, defaultRate: 8.75, maxYears: 30, presetsAmt: [{lbl:'25L', val:2500000}, {lbl:'50L', val:5000000}, {lbl:'1 Cr', val:10000000}], presetsRate: [7.5, 8.5, 9.5] },
    car: { label: "Car Loan Amount", minAmt: 100000, maxAmt: 5000000, stepAmt: 25000, defaultAmt: 1200000, defaultRate: 9.50, maxYears: 7, presetsAmt: [{lbl:'5L', val:500000}, {lbl:'12L', val:1200000}, {lbl:'25L', val:2500000}], presetsRate: [8.5, 9.5, 11.0] },
    personal: { label: "Personal Loan Amount", minAmt: 50000, maxAmt: 2500000, stepAmt: 10000, defaultAmt: 400000, defaultRate: 12.50, maxYears: 5, presetsAmt: [{lbl:'1L', val:100000}, {lbl:'4L', val:400000}, {lbl:'10L', val:1000000}], presetsRate: [10.5, 12.5, 15.0] }
};

function formatDisplayCurrency(num) { return '₹' + Math.round(num).toLocaleString('en-IN'); }
function adjustPrincipal(val) { document.getElementById('num_principal').value = val; document.getElementById('range_principal').value = val; syncSliderTrackColor(document.getElementById('range_principal')); runMainCalculatorEngine(); }
function adjustRate(val) { document.getElementById('num_rate').value = val; document.getElementById('range_rate').value = val; syncSliderTrackColor(document.getElementById('range_rate')); runMainCalculatorEngine(); }
function adjustTenure(val) { document.getElementById('num_tenure').value = val; document.getElementById('range_tenure').value = val; syncSliderTrackColor(document.getElementById('range_tenure')); runMainCalculatorEngine(); }

function changeLoanType(productKey) {
    activeProductKey = productKey;
    ['home', 'car', 'personal'].forEach(k => document.getElementById(`tab_${k}`).classList.toggle('active', k === productKey));
    
    const config = loanProductProfiles[productKey];
    document.getElementById('mainAmountLabel').textContent = config.label;
    
    const sP = document.getElementById('range_principal'), nP = document.getElementById('num_principal');
    sP.min = nP.min = config.minAmt; sP.max = nP.max = config.maxAmt; sP.step = nP.step = config.stepAmt; sP.value = nP.value = config.defaultAmt;

    document.getElementById('principalPresets').innerHTML = config.presetsAmt.map(i => `<button type="button" class="preset-pill-btn" onclick="adjustPrincipal(${i.val})">${i.lbl}</button>`).join('');
    document.getElementById('ratePresets').innerHTML = config.presetsRate.map(p => `<button type="button" class="preset-pill-btn" onclick="adjustRate(${p})">${p}%</button>`).join('');

    adjustRate(config.defaultRate);
    changeTenureUnit(localTenureUnit); 
    adjustTenure(localTenureUnit === 'years' ? config.maxYears : config.maxYears * 12);
}

function runMainCalculatorEngine() {
    const p = parseFloat(document.getElementById('num_principal').value) || 0;
    const r = parseFloat(document.getElementById('num_rate').value) || 0;
    const t = parseInt(document.getElementById('num_tenure').value) || 0;
    
    let totalMonths = (localTenureUnit === 'years') ? t * 12 : t;
    let monthlyFraction = r / 12 / 100;
    
    let emi = (monthlyFraction > 0 && totalMonths > 0) ? (p * monthlyFraction * Math.pow(1 + monthlyFraction, totalMonths)) / (Math.pow(1 + monthlyFraction, totalMonths) - 1) : (p / (totalMonths || 1));
    emi = Math.round(emi * 100) / 100;
    
    let totalPayout = Math.round(emi * totalMonths * 100) / 100;
    let totalInterest = Math.round((totalPayout - p) * 100) / 100;

    document.getElementById('out_emi').textContent = formatDisplayCurrency(emi);
    document.getElementById('out_principal').textContent = formatDisplayCurrency(p);
    document.getElementById('out_interest').textContent = formatDisplayCurrency(totalInterest);

    let pRatio = totalPayout > 0 ? ((p / totalPayout) * 100).toFixed(1) : 0;
    let iRatio = totalPayout > 0 ? ((totalInterest / totalPayout) * 100).toFixed(1) : 0;
    document.getElementById('ratio_p').textContent = pRatio + '%';
    document.getElementById('ratio_i').textContent = iRatio + '%';

    const alertBox = document.getElementById('insightBox'), alertText = document.getElementById('insightText');
    if (iRatio > 50) { alertBox.className = "smart-insight-banner red-alert"; alertText.textContent = "Warning: Interest costs exceed principal loan amount. Consider lowering tenure."; }
    else if (iRatio > 25) { alertBox.className = "smart-insight-banner yellow-alert"; alertText.textContent = "Balanced Ratio: Interest burden is stable. Good structural configuration."; }
    else { alertBox.className = "smart-insight-banner green-alert"; alertText.textContent = "Excellent Setup: Efficient tenure selection! Minimal interest payout overhead."; }

    renderCharts(p, totalInterest);
    compileAmortization(p, totalMonths, monthlyFraction, emi);
}

function renderCharts(p, i) {
    if (donutChartObject) donutChartObject.destroy();
    donutChartObject = new Chart(document.getElementById('loanDonutChart'), {
        type: 'doughnut',
        data: { datasets: [{ data: [p, i], backgroundColor: ['#00b4d8', '#ffb703'], borderWidth: 2, borderColor: '#ffffff' }] },
        options: { responsive: false, cutout: '70%', plugins: { legend: { display: false } } }
    });
}

function compileAmortization(principal, totalMonths, monthlyFraction, emiAmount) {
    let unservicedBalance = principal, chosenMonth = parseInt(document.getElementById('startMonthDropdown').value);
    let chosenYear = parseInt(document.getElementById('startYearInput').value) || 2026, groupingMode = document.getElementById('yearTypeDropdown').value;
    
    loanTableData = {};
    let runningPrincipalSum = 0, loopMonth = chosenMonth, loopYear = chosenYear;

    for (let index = 1; index <= totalMonths; index++) {
        let interestAccrued = unservicedBalance * monthlyFraction;
        let principalCleared = Math.min(unservicedBalance, emiAmount - interestAccrued);
        
        unservicedBalance -= principalCleared;
        runningPrincipalSum += principalCleared;

        let groupKey = (groupingMode === 'financial') ? 'FY' + ((loopMonth >= 3) ? (loopYear + 1) : loopYear) : loopYear;
        let groupLabel = (groupingMode === 'financial') ? "FY" + String(((loopMonth >= 3) ? (loopYear + 1) : loopYear) % 100).padStart(2, '0') : "Year " + loopYear;

        if (!loanTableData[groupKey]) loanTableData[groupKey] = { principal: 0, interest: 0, balance: 0, months: [], groupLabel: groupLabel };
        
        loanTableData[groupKey]['principal'] += principalCleared;
        loanTableData[groupKey]['interest'] += interestAccrued;
        loanTableData[groupKey]['balance'] = Math.max(0, unservicedBalance);
        loanTableData[groupKey]['months'].push({ monthLabel: monthNamesList[loopMonth], principalComponent: principalCleared, interestComponent: interestAccrued, currentBalance: Math.max(0, unservicedBalance), percentagePaid: Math.min(100, ((runningPrincipalSum / principal) * 100)).toFixed(2) });

        loopMonth++; if (loopMonth > 11) { loopMonth = 0; loopYear++; }
    }

    let accP = 0;
    Object.keys(loanTableData).forEach(k => { accP += loanTableData[k].principal; loanTableData[k].percentagePaid = Math.min(100, ((accP / principal) * 100)).toFixed(2); });

    const htmlTableBody = document.getElementById('tableDataRows');
    if (!htmlTableBody) return; htmlTableBody.innerHTML = "";

    let tLabels = [], dPrincipals = [], dInterests = [], dBalances = [];

    Object.keys(loanTableData).forEach((yearKey, idxPos) => {
        let block = loanTableData[yearKey];
        tLabels.push(block.groupLabel); dPrincipals.push(Math.round(block.principal)); dInterests.push(Math.round(block.interest)); dBalances.push(Math.round(block.balance));

        let trParent = document.createElement('tr');
        trParent.className = 'master-row';
        trParent.innerHTML = `<td class="toggle-cell"><span class="expand-icon-state">┼</span> ${block.groupLabel}</td><td>${formatDisplayCurrency(block.principal)}</td><td>${formatDisplayCurrency(block.interest)}</td><td>${formatDisplayCurrency(block.principal + block.interest)}</td><td>${formatDisplayCurrency(block.balance)}</td><td><span class="payout-progress-pill">${block.percentagePaid}%</span></td>`;
        
        let subClass = `sub_row_group_${idxPos}`;
        trParent.onclick = () => {
            const rows = document.getElementsByClassName(subClass);
            trParent.classList.toggle('open-row');
            const isOpen = trParent.classList.contains('open-row');
            for(let i=0; i<rows.length; i++) rows[i].classList.toggle('visible-month', isOpen);
            trParent.querySelector('.expand-icon-state').textContent = isOpen ? '⊟' : '┼';
        };
        htmlTableBody.appendChild(trParent);

        block.months.forEach(m => {
            let trChild = document.createElement('tr'); trChild.className = `slave-row ${subClass}`;
            trChild.innerHTML = `<td class="sub-month-text-indent">${m.monthLabel}</td><td>${formatDisplayCurrency(m.principalComponent)}</td><td>${formatDisplayCurrency(m.interestComponent)}</td><td>${formatDisplayCurrency(m.principalComponent + m.interestComponent)}</td><td>${formatDisplayCurrency(m.currentBalance)}</td><td><span class="payout-progress-pill">${m.percentagePaid}%</span></td>`;
            htmlTableBody.appendChild(trChild);
        });
    });

    renderTimelineGraph(tLabels, dPrincipals, dInterests, dBalances);
}

function renderTimelineGraph(labels, pData, iData, bData) {
    if (timelineChartObject) timelineChartObject.destroy();
    const ctx = document.getElementById('paymentTimelineChart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 300); grad.addColorStop(0, 'rgba(230, 57, 70, 0.2)'); grad.addColorStop(1, 'rgba(230, 57, 70, 0.0)');

    timelineChartObject = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Principal', data: pData, backgroundColor: '#00b4d8', borderRadius: 4, stack: 'stk', order: 2 },
                { label: 'Interest', data: iData, backgroundColor: '#ffb703', borderRadius: 4, stack: 'stk', order: 2 },
                { label: 'Balance Owed', data: bData, type: 'line', borderColor: '#e63946', borderWidth: 3, fill: true, backgroundColor: grad, order: 1, yAxisID: 'y2' }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true }, y2: { position: 'right', grid: { display: false } } } }
    });
}

function changeTenureUnit(mode) {
    localTenureUnit = mode; document.getElementById('hidden_tenure_type').value = mode;
    const sRef = document.getElementById('range_tenure'), nRef = document.getElementById('num_tenure');
    let val = parseInt(nRef.value) || 0, ceiling = loanProductProfiles[activeProductKey].maxYears;

    document.getElementById('toggle_yr').classList.toggle('active', mode === 'years');
    document.getElementById('toggle_mo').classList.toggle('active', mode === 'months');

    if (mode === 'years') {
        sRef.min = 1; sRef.max = ceiling; sRef.step = 1; if (val > ceiling) val = Math.round(val / 12);
        sRef.value = nRef.value = Math.max(1, Math.min(ceiling, val));
        document.getElementById('tenurePresets').innerHTML = [Math.round(ceiling/3), Math.round((ceiling/3)*2), ceiling].map(v => `<button type="button" class="preset-pill-btn" onclick="adjustTenure(${v})">${v} Yrs</button>`).join('');
    } else {
        let maxMo = ceiling * 12; sRef.min = 1; sRef.max = maxMo; sRef.step = 1; if (val <= ceiling) val = val * 12;
        sRef.value = nRef.value = Math.max(1, Math.min(maxMo, val));
        document.getElementById('tenurePresets').innerHTML = [Math.round(maxMo/3), Math.round((maxMo/3)*2), maxMo].map(v => `<button type="button" class="preset-pill-btn" onclick="adjustTenure(${v})">${v} Mo</button>`).join('');
    }
    syncSliderTrackColor(sRef); runMainCalculatorEngine();
}

function syncSliderTrackColor(el) {
    const pct = ((el.value - el.min) / (el.max - el.min)) * 100;
    el.style.background = `linear-gradient(to right, #00b4d8 ${pct}%, #e2e8f0 ${pct}%)`;
}

function bindSync(sId, nId) {
    const s = document.getElementById(sId), n = document.getElementById(nId);
    s.addEventListener('input', () => { n.value = s.value; syncSliderTrackColor(s); runMainCalculatorEngine(); });
    n.addEventListener('input', () => { s.value = n.value; syncSliderTrackColor(s); runMainCalculatorEngine(); });
    syncSliderTrackColor(s);
}

function toggleInterfaceTheme() {
    let t = (document.documentElement.getAttribute('data-theme') === 'dark') ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', t); localStorage.setItem('user-selected-theme', t);
    applyGlobalChartStyleRules(t); runMainCalculatorEngine();
}

function applyGlobalChartStyleRules(t) {
    const i = document.getElementById('modeIcon'), txt = document.getElementById('modeText');
    if (t === 'dark') { i.textContent = '☀️'; txt.textContent = 'Light Mode'; Chart.defaults.color = '#94a3b8'; Chart.defaults.borderColor = '#334155'; }
    else { i.textContent = '🌙'; txt.textContent = 'Dark Mode'; Chart.defaults.color = '#666666'; Chart.defaults.borderColor = '#e2e8f0'; }
}

function populateMonthGrid() {
    const selectedMonth = parseInt(document.getElementById('startMonthDropdown').value), selectedYear = parseInt(document.getElementById('startYearInput').value);
    document.getElementById('popupMonthGrid').innerHTML = monthNamesList.map((m, idx) => `<div class="popup-month-cell ${(idx === selectedMonth && popupDisplayedYear === selectedYear) ? 'selected-month' : ''}" onclick="selectPopupMonth(${idx})">${m}</div>`).join('');
    document.getElementById('popupYearLabel').textContent = popupDisplayedYear;
}

function shiftPopupYear(d) { popupDisplayedYear += d; populateMonthGrid(); }
function selectPopupMonth(idx) { document.getElementById('startMonthDropdown').value = idx; document.getElementById('startYearInput').value = popupDisplayedYear; document.getElementById('pickerDisplayText').textContent = monthNamesList[idx] + ' ' + popupDisplayedYear; document.getElementById('monthYearPopup').classList.remove('open'); runMainCalculatorEngine(); }
function toggleMonthYearPopup() { const p = document.getElementById('monthYearPopup'); if (!p.classList.contains('open')) { popupDisplayedYear = parseInt(document.getElementById('startYearInput').value) || 2026; populateMonthGrid(); } p.classList.toggle('open'); }

function downloadExcelData() {
    if(Object.keys(loanTableData).length === 0) return;
    let csv = "data:text/csv;charset=utf-8,Timeline Frame,Principal Paid (A),Interest Component (B),Total Service Payment,Remaining Balance Owed,Loan Paid To Date (%)\r\n";
    Object.keys(loanTableData).forEach(k => {
        let b = loanTableData[k]; csv += `${b.groupLabel},${Math.round(b.principal)},${Math.round(b.interest)},${Math.round(b.principal + b.interest)},${Math.round(b.balance)},${b.percentagePaid}\r\n`;
        b.months.forEach(m => { csv += `${m.monthLabel} ${b.groupLabel},${Math.round(m.principalComponent)},${Math.round(m.interestComponent)},${Math.round(m.principalComponent + m.interestComponent)},${Math.round(m.currentBalance)},${m.percentagePaid}\r\n`; });
    });
    const el = document.createElement("a"); el.setAttribute("href", encodeURI(csv)); el.setAttribute("download", `${activeProductKey}_loan_repayment_sheet.csv`); document.body.appendChild(el); el.click(); document.body.removeChild(el);
}

function copyDashboardLink() {
    const shareUrl = window.location.origin + window.location.pathname + `?p=${document.getElementById('num_principal').value}&r=${document.getElementById('num_rate').value}&t=${document.getElementById('num_tenure').value}&m=${localTenureUnit}&lt=${activeProductKey}`;
    navigator.clipboard.writeText(shareUrl).then(() => alert("🔗 Shareable setup link copied to clipboard!")).catch(() => alert("Share Link: " + shareUrl));
}

document.addEventListener('click', (e) => { if (!document.querySelector('.month-year-picker-wrapper').contains(e.target)) document.getElementById('monthYearPopup').classList.remove('open'); });

window.addEventListener('DOMContentLoaded', () => {
    const t = localStorage.getItem('user-selected-theme') || 'light';
    document.documentElement.setAttribute('data-theme', t); applyGlobalChartStyleRules(t);
    changeLoanType(activeProductKey); bindSync('range_principal', 'num_principal'); bindSync('range_rate', 'num_rate'); bindSync('range_tenure', 'num_tenure');
    document.getElementById('pickerDisplayText').textContent = monthNamesList[5] + ' ' + 2026;
    runMainCalculatorEngine();
});
</script>
</body>
</html>