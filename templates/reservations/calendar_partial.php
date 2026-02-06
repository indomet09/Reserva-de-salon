<?php
// Calendar Grid Partial
// Requires: $currentYear, $currentMonth, $reservationsByDate
// Optional: $eventColors, $monthNames

$eventColors = $eventColors ?? [
    '#dc2626', '#ea580c', '#ca8a04', '#16a34a', '#0891b2',
    '#2563eb', '#7c3aed', '#c026d3', '#db2777', '#059669'
];

$monthNames = $monthNames ?? [
    '', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];

$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay);
$today = date('Y-m-d');
?>

<div class="calendar-nav">
    <a href="?view=calendar&year=<?= $currentMonth == 1 ? $currentYear - 1 : $currentYear ?>&month=<?= $currentMonth == 1 ? 12 : $currentMonth - 1 ?>"
       class="btn btn-secondary btn-sm ajax-calendar-nav">
        <svg class="icon" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6" /></svg> Anterior
    </a>
    <h3 class="calendar-title"><?= $monthNames[$currentMonth] ?> <?= $currentYear ?></h3>
    <a href="?view=calendar&year=<?= $currentMonth == 12 ? $currentYear + 1 : $currentYear ?>&month=<?= $currentMonth == 12 ? 1 : $currentMonth + 1 ?>"
       class="btn btn-secondary btn-sm ajax-calendar-nav">
        Siguiente <svg class="icon" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6" /></svg>
    </a>
</div>

<div class="calendar">
    <div class="calendar-header">
        <div class="calendar-day-name">Dom</div>
        <div class="calendar-day-name">Lun</div>
        <div class="calendar-day-name">Mar</div>
        <div class="calendar-day-name">Mié</div>
        <div class="calendar-day-name">Jue</div>
        <div class="calendar-day-name">Vie</div>
        <div class="calendar-day-name">Sáb</div>
    </div>
    <div class="calendar-body">
        <?php
        // Empty cells before first day
        for ($i = 0; $i < $dayOfWeek; $i++):
        ?>
            <div class="calendar-cell empty"></div>
        <?php endfor; ?>

        <?php
        for ($day = 1; $day <= $daysInMonth; $day++):
            $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
            $isToday = $dateStr === $today;
            $hasReservations = isset($reservationsByDate[$dateStr]);
            $dayReservations = $reservationsByDate[$dateStr] ?? [];
        ?>
            <div class="calendar-cell <?= $isToday ? 'today' : '' ?> <?= $hasReservations ? 'has-events' : '' ?>">
                <div class="calendar-date"><?= $day ?></div>
                <?php if ($hasReservations): ?>
                    <div class="calendar-events">
                        <?php foreach ($dayReservations as $r):
                            $colorIndex = abs(crc32($r['area'])) % count($eventColors);
                            $eventColor = $eventColors[$colorIndex];
                        ?>
                            <div class="calendar-event"
                                style="background: <?= $eventColor ?>; border-left: 3px solid <?= $eventColor ?>;"
                                onclick="openReservationModal('<?= $r['id'] ?>')"
                                title="<?= htmlspecialchars($r['area'] . ' - ' . $r['responsible'] . ' (' . substr($r['start_time'], 0, 5) . ' a ' . substr($r['end_time'], 0, 5) . ')') ?>">
                                <span class="event-time"><?= substr($r['start_time'], 0, 5) ?>-<?= substr($r['end_time'], 0, 5) ?></span>
                                <span class="event-title"><?= htmlspecialchars(substr($r['area'], 0, 12)) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>