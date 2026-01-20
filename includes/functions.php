<?php
function badge_for_points($points){
    if($points >= 600) return 'Platinum';
    if($points >= 301) return 'Gold';
    if($points >= 101) return 'Silver';
    return 'Bronze';
}
function badge_color($badge){
    return [
        'Bronze'=>'#d97706',
        'Silver'=>'#94a3b8',
        'Gold'=>'#eab308',
        'Platinum'=>'#7c3aed'
    ][$badge] ?? '#64748b';
}
?>
