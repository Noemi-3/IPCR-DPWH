<?php
require '../config/database.php';
require '../includes/session.php';

// 1. Determine TARGET User and TARGET Period
// If URL parameters exist, use them. Otherwise, fall back to the logged-in session.
$user_id   = isset($_GET['uid']) ? intval($_GET['uid']) : $_SESSION['user_id'];
$period_id = isset($_GET['period_id']) ? intval($_GET['period_id']) : $_SESSION['period_id'];

// 2. Fetch User Data
$u = $conn->prepare("SELECT full_name, position, division FROM users WHERE id=?");
$u->bind_param("i", $user_id);
$u->execute();
$user = $u->get_result()->fetch_assoc();

// 3. Fetch Period Data
$p = $conn->prepare("SELECT month, year FROM login_periods WHERE id=?");
$p->bind_param("i", $period_id);
$p->execute();
$period = $p->get_result()->fetch_assoc();
$period_display = strtoupper($period['month'] . ' ' . $period['year']); 

// 4. Fetch Tasks & Ratings (STRICTLY FILTERED BY TARGET PERIOD AND USER)
$sql = "
SELECT 
    t.id AS task_id,
    t.task_code, 
    t.task_title, 
    t.success_indicator,
    ta.actual_accomplishment,
    ta.q_rating,
    ta.e_rating,
    ta.t_rating
FROM user_tasks ut
JOIN tasks t ON t.id = ut.task_id
LEFT JOIN task_accomplishments ta ON (ta.task_id = t.id AND ta.user_id = ? AND ta.period_id = ?)
WHERE ut.user_id = ? AND ut.period_id = ?
ORDER BY 
  CAST(SUBSTRING_INDEX(t.task_code,'.',1) AS UNSIGNED), 
  CAST(SUBSTRING_INDEX(t.task_code,'.',-1) AS UNSIGNED)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $period_id, $user_id, $period_id);
$stmt->execute();
$result = $stmt->get_result();

$tasks_data = [];
$grand_total = 0;
$count = 0;

while($row = $result->fetch_assoc()){
    // Calculate row average
    $q = $row['q_rating'] ?? 0;
    $e = $row['e_rating'] ?? 0;
    $t = $row['t_rating'] ?? 0;
    
    $divisor = 0;
    $sum = 0;
    if($q > 0) { $sum+=$q; $divisor++; }
    if($e > 0) { $sum+=$e; $divisor++; }
    if($t > 0) { $sum+=$t; $divisor++; }
    
    $avg = $divisor > 0 ? $sum/$divisor : 0;
    
    if($avg > 0) {
        $grand_total += $avg;
        $count++;
    }

    $row['avg'] = number_format($avg, 2);
    $tasks_data[] = $row;
}

$final_rating = $count > 0 ? number_format($grand_total / $count, 2) : "0.00";

// Adjectival Rating Logic
$adjectival = "";
if ($final_rating >= 4.51) $adjectival = "Outstanding";
else if ($final_rating >= 3.51) $adjectival = "Very Satisfactory";
else if ($final_rating >= 2.51) $adjectival = "Satisfactory";
else if ($final_rating >= 1.51) $adjectival = "Unsatisfactory";
else if ($final_rating > 0) $adjectival = "Poor";
else $adjectival = "---"; // If completely empty

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IPCR Print Format</title>
    <style>
        /* PRINT SETTINGS */
        @page { size: A4 landscape; margin: 10mm; }
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.3;
        }

        /* UTILITIES */
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-underline { text-decoration: underline; }
        .uppercase { text-transform: uppercase; }
        .w-100 { width: 100%; }
        
        /* TABLE STYLES - Strict borders for government forms */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }
        
        /* HEADER */
        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            border: 1px solid #000;
            padding: 5px;
            margin-bottom: 15px;
        }
        
        .commitment-p {
            margin-bottom: 15px;
            text-align: justify;
        }
        
        /* SIGNATORY BOXES */
        .sig-table td { border: 1px solid #000; padding: 5px; }
        .sig-label { font-size: 9px; margin-bottom: 20px; }
        .sig-name { font-weight: bold; font-size: 12px; text-align: center; text-transform: uppercase; }
        
        /* CONTENT TABLE */
        .main-table th { background-color: #eee; text-align: center; font-weight: bold; font-size: 10px; }
        .rating-col { width: 30px; text-align: center; }
        
        /* PRESERVE UNDERLINES FROM DATABASE */
        u { text-decoration: underline; font-weight: bold; }
    </style>
</head>
<body onload="window.print()">

    <div style="text-align: right; font-size: 9px; margin-bottom: 2px;">DPWH SPMS Form No. 1</div>
    <div class="header-title">INDIVIDUAL PERFORMANCE COMMITMENT and REVIEW (IPCR) FORM</div>

    <p class="commitment-p">
        I, <span class="text-bold text-underline uppercase"><?= htmlspecialchars($user['full_name']) ?></span>, 
        <span class="text-bold text-underline"><?= htmlspecialchars($user['position']) ?></span> of 
        <span class="text-bold text-underline"><?= htmlspecialchars($user['division']) ?></span>, 
        DPWH - Butuan City DEO, commit to deliver and agree to be rated on the attainment of the following targets 
        in accordance with the indicated measures for the period <span class="text-bold text-underline"><?= $period_display ?></span>.
    </p>

    <table class="sig-table">
        <tr>
            <td width="60%">
                <div class="sig-label">Approved by:</div>
                <div class="text-center text-bold" style="margin-bottom: 5px;">District Office PMT Chairman</div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div style="text-align: center; width: 50%;">
                        <div class="sig-name" style="text-decoration: underline;">JOSE CAESAR A. RADAZA</div>
                        <div style="font-size: 10px;">Name</div>
                    </div>
                    <div style="text-align: center; width: 50%;">
                        <div class="sig-name" style="text-decoration: underline;">District Engineer</div>
                        <div style="font-size: 10px;">Position</div>
                    </div>
                </div>
            </td>
            <td width="40%" style="vertical-align: bottom;">
                <div class="text-center">
                    <div style="border-bottom: 1px solid #000; width: 80%; margin: 0 auto;">&nbsp;</div>
                    <div style="font-size: 10px; margin-top: 2px;">Signature of Ratee</div>
                </div>
                <div style="margin-top: 10px;">
                    Date Prepared: <span style="text-decoration: underline;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2" width="15%">Output</th>
                <th rowspan="2" width="25%">Success Indicators<br>(Targets + Measures)</th>
                <th rowspan="2" width="40%">Actual Accomplishments</th>
                <th colspan="4">Rating</th>
                <th rowspan="2" width="10%">Remarks</th>
            </tr>
            <tr>
                <th class="rating-col">Q</th>
                <th class="rating-col">E</th>
                <th class="rating-col">T</th>
                <th class="rating-col">Avg</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($tasks_data as $row): ?>
            <tr>
                <td>
                    <b><?= htmlspecialchars($row['task_code']) ?></b><br>
                    <?= nl2br(htmlspecialchars($row['task_title'])) ?>
                </td>
                <td><?= nl2br(htmlspecialchars($row['success_indicator'])) ?></td>
                <td>
                    <?= $row['actual_accomplishment'] ?>
                </td>
                <td class="text-center"><?= $row['q_rating'] ?: '' ?></td>
                <td class="text-center"><?= $row['e_rating'] ?: '' ?></td>
                <td class="text-center"><?= $row['t_rating'] ?: '' ?></td>
                <td class="text-center text-bold"><?= $row['avg'] > 0 ? $row['avg'] : '' ?></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
            
            <tr>
                <td colspan="6" style="text-align: right; font-weight: bold;">Final Average Rating:</td>
                <td class="text-center text-bold" style="background: #f0f0f0;"><?= $final_rating ?></td>
                <td class="text-center text-bold"><?= $adjectival ?></td>
            </tr>
        </tbody>
    </table>

    <div style="font-size: 10px; margin-bottom: 5px;">
        Rater comments and recommendation for development purposes or rewards/promotion.
    </div>
    <div style="border: 1px solid #000; height: 30px; margin-bottom: 10px;"></div>

    <table class="sig-table">
        <tr>
            <td colspan="4" style="font-size: 10px; font-weight: bold; border-bottom: none;">
                The above rating has been discussed with:
            </td>
        </tr>
        <tr>
            <td width="25%">
                <div class="sig-label">Name and Signature of Ratee:</div>
                <br>
                <div class="sig-name text-underline"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="text-center" style="font-size: 10px;"><?= htmlspecialchars($user['position']) ?></div>
                <div style="margin-top: 10px; font-size: 9px;">Date: ______________</div>
            </td>
            <td width="25%">
                <div class="sig-label">Name and Signature of Initial Rater:</div>
                <br>
                <div class="sig-name text-underline">JAN MARK S. GUIBONE</div>
                <div class="text-center" style="font-size: 10px;">Computer Maintenance Technologist II</div>
                <div style="margin-top: 10px; font-size: 9px;">Date: ______________</div>
            </td>
            <td width="25%">
                <div class="sig-label">Name and Signature of Final Rater:</div>
                <br>
                <div class="sig-name text-underline">JOSE CAESAR A. RADAZA</div>
                <div class="text-center" style="font-size: 10px;">District Engineer</div>
                <div style="margin-top: 10px; font-size: 9px;">Date: ______________</div>
            </td>
            <td width="25%" style="vertical-align: middle; text-align: center;">
                 <div style="font-weight: bold; font-size: 10px;">Final Rating</div>
                 <div style="font-size: 24px; font-weight: bold; margin: 10px 0;"><?= $final_rating ?></div>
                 <div style="font-size: 11px;"><?= $adjectival ?></div>
            </td>
        </tr>
    </table>

</body>
</html>