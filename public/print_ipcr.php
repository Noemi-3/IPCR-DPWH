<?php
require '../config/database.php';
require '../includes/session.php';

// 1. Determine TARGET User and TARGET Period
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

// Format the period correctly
$period_display = strtoupper($period['month'] . ', ' . $period['year']); 

// 4. Fetch Tasks & Ratings
$sql = "
SELECT 
    t.id AS task_id,
    t.task_code, 
    t.task_title, 
    t.success_indicator,
    ta.actual_accomplishment,
    ta.q_rating,
    ta.e_rating,
    ta.t_rating,
    ta.remarks 
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
else $adjectival = "---";

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
        
        /* TABLE STYLES */
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
            font-size: 15px;
            border: 1px solid #000;
            padding: 4px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        
        .commitment-p {
            margin-bottom: 15px;
            text-align: left;
            font-size: 11px;
        }
        
        /* MAIN CONTENT TABLE */
        .main-table th { background-color: #fcfcfc; text-align: center; font-weight: bold; font-size: 10px; vertical-align: middle;}
        .rating-col { width: 30px; text-align: center; }
        
        u { text-decoration: underline; font-weight: bold; }
    </style>
</head>
<body onload="window.print()">

    <div style="text-align: right; font-size: 9px; margin-bottom: 2px;">DPWH SPMS Form No. 1</div>
    <div class="header-title">INDIVIDUAL PERFORMANCE COMMITMENT and REVIEW (IPCR) FORM</div>

    <p class="commitment-p">
        I, <span class="text-bold text-underline uppercase"><?= htmlspecialchars($user['full_name']) ?></span>, 
        <span class="text-bold text-underline"><?= htmlspecialchars($user['position']) ?> <?= !empty($user['division']) ? '/ ' . htmlspecialchars($user['division']) : '' ?></span>, of 
        <span class="text-bold text-underline">DPWH – Butuan City DEO</span>, commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period <span class="text-bold text-underline uppercase"><?= $period_display ?></span>.
    </p>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; border: none;">
        <tr>
            <td style="width: 60%; padding: 0; border: none; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; margin-bottom: 0;">
                    <tr>
                        <td style="width: 15%; border: 1px solid #000; padding: 2px 4px; font-size: 10px;">Approved by:</td>
                        <td colspan="3" style="border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold; font-size: 12px;">District Office PMT Chairman</td>
                    </tr>
                    <tr>
                        <td style="width: 15%; border: 1px solid #000; padding: 2px 4px; font-size: 10px;">Signature:</td>
                        <td style="width: 35%; border: 1px solid #000; padding: 2px 4px;"></td>
                        <td style="width: 15%; border: 1px solid #000; padding: 2px 4px; font-size: 10px;">Position:</td>
                        <td style="width: 35%; border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold; font-size: 10px;">District Engineer</td>
                    </tr>
                    <tr>
                        <td style="width: 15%; border: 1px solid #000; padding: 2px 4px; font-size: 10px;">Name:</td>
                        <td style="width: 35%; border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold; font-size: 10px;">JOSE CAESAR A. RADAZA</td>
                        <td style="width: 15%; border: 1px solid #000; padding: 2px 4px; font-size: 10px;">Office:</td>
                        <td style="width: 35%; border: 1px solid #000; padding: 2px 4px; text-align: center; font-weight: bold; font-size: 10px;">Office of the District Engineer</td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%; padding: 0 0 0 20px; border: none; vertical-align: bottom;">
                <div style="text-align: center; margin-bottom: 12px;">
                    <div style="border-bottom: 1px solid #000; width: 85%; margin: 0 auto;"></div>
                    <div style="font-size: 10px; font-weight: bold; margin-top: 2px;">Signature of Ratee</div>
                </div>
                <div style="font-size: 10px; font-weight: bold; padding-left: 20px;">
                    Date Prepared: <span style="display: inline-block; width: 50%; border-bottom: 1px solid #000; margin-left: 5px;">&nbsp;</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="main-table" style="border: 2px solid #000;">
        <thead>
            <tr>
                <th rowspan="2" width="15%">Output</th>
                <th rowspan="2" width="25%">Success Indicators<br>(Targets + Measures)</th>
                <th rowspan="2" width="40%">Actual Accomplishments</th>
                <th colspan="3">Rating</th>
                <th rowspan="2" class="rating-col">Average</th>
                <th rowspan="2" width="10%">Remarks</th>
            </tr>
            <tr>
                <th class="rating-col">Q</th>
                <th class="rating-col">E</th>
                <th class="rating-col">T</th>
            </tr>
            <tr>
                <th colspan="2" style="text-align: left; padding-left: 10px; font-style: italic; font-weight: normal; background: #fff;">TO BE FILLED BEGINNING OF THE SEMESTRAL RATING PERIOD</th>
                <th colspan="6" style="text-align: left; padding-left: 10px; font-style: italic; font-weight: normal; background: #fff;">TO BE FILLED DURING EVALUATION</th>
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
                
                <td style="font-size: 10px; text-align: left; padding: 4px;">
                    <?= !empty($row['remarks']) ? nl2br(htmlspecialchars($row['remarks'])) : '' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <tr>
                <td colspan="3" style="font-style: italic; font-size: 9px; padding: 2px 4px; border-right: none;">Note: Use additional sheet/s if necessary:</td>
                <td style="border-left: none;"></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="6" style="text-align: right; font-weight: bold; padding: 4px;">Total Rating</td>
                <td class="text-center text-bold"><?= $grand_total > 0 ? number_format($grand_total, 2) : '' ?></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="6" style="text-align: right; font-weight: bold; padding: 4px;">Final Average Rating</td>
                <td class="text-center text-bold"><?= $final_rating ?></td>
                <td class="text-center text-bold"><?= $adjectival ?></td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px; border: 2px solid #000;">
        <tr>
            <td style="font-size: 10px; padding: 2px 4px; border-bottom: 1px solid #000;">
                Rater comments and recommendation for development purposes or rewards/promotion. <i style="font-size: 9px;">(Note: Use additional sheet/s if necessary)</i>
            </td>
        </tr>
        <tr>
            <td style="height: 25px;"></td>
        </tr>
    </table>

    <div style="font-size: 10px; margin-bottom: 2px;">The above rating has been discussed with:</div>
    <table style="width: 100%; border-collapse: collapse; border: 2px solid #000;">
        <tr>
            <td style="width: 13%; font-weight: bold; font-size: 10px; border: 1px solid #000; padding: 4px;">Name and<br>Signature of<br>Ratee:</td>
            <td style="width: 20%; font-weight: bold; font-size: 11px; text-align: center; border: 1px solid #000; padding: 4px; vertical-align: middle; text-transform: uppercase;"><?= htmlspecialchars($user['full_name']) ?></td>
            <td style="width: 13%; font-weight: bold; font-size: 10px; border: 1px solid #000; padding: 4px;">Name and Signature<br>of Initial Rater:</td>
            <td style="width: 20%; font-weight: bold; font-size: 11px; text-align: center; border: 1px solid #000; padding: 4px; vertical-align: middle;">JAN MARK S. GUIBONE</td>
            <td style="width: 14%; font-weight: bold; font-size: 10px; border: 1px solid #000; padding: 4px;">Name and Signature<br>of Final Rater:</td>
            <td style="width: 20%; font-weight: bold; font-size: 11px; text-align: center; border: 1px solid #000; padding: 4px; vertical-align: middle;">JOSE CAESAR A. RADAZA</td>
        </tr>
        <tr>
            <td style="font-size: 10px; border: 1px solid #000; padding: 2px 4px;">Position:</td>
            <td style="font-size: 10px; text-align: center; border: 1px solid #000; padding: 2px 4px;"><?= htmlspecialchars($user['position']) ?> <?= !empty($user['division']) ? '/ ' . htmlspecialchars($user['division']) : '' ?></td>
            <td style="font-size: 10px; border: 1px solid #000; padding: 2px 4px;">Position:</td>
            <td style="font-size: 10px; text-align: center; border: 1px solid #000; padding: 2px 4px;">Computer Maintenance Technologist II</td>
            <td style="font-size: 10px; border: 1px solid #000; padding: 2px 4px;">Position:</td>
            <td style="font-size: 10px; text-align: center; border: 1px solid #000; padding: 2px 4px;">District Engineer</td>
        </tr>
        <tr>
            <td style="font-size: 10px; border: 1px solid #000; padding: 2px 4px;">Date:</td>
            <td style="font-size: 10px; text-align: center; border: 1px solid #000; padding: 2px 4px;"></td>
            <td style="font-size: 10px; border: 1px solid #000; padding: 2px 4px;">Date:</td>
            <td style="font-size: 10px; text-align: center; border: 1px solid #000; padding: 2px 4px;"></td>
            <td style="font-size: 10px; border: 1px solid #000; padding: 2px 4px;">Date:</td>
            <td style="font-size: 10px; text-align: center; border: 1px solid #000; padding: 2px 4px;"></td>
        </tr>
    </table>

</body>
</html>