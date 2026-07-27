<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Pagină de admin dedicată sincronizării Aperta - un rezumat simplu peste
 * Action Scheduler, filtrat doar pe grupul 'aperta-sync' (vezi
 * includes/aperta-sync.php), ca sa nu mai fie nevoie sa cauti prin ecranul
 * generic WooCommerce > Stare > Acțiuni programate.
 */

function papetarie_storefront_aperta_lavinia_cleanup_codes(): array
{
    return [
        ['cod_produs' => '2618', 'sku' => ''],
        ['cod_produs' => 'CMC010', 'sku' => ''],
        ['cod_produs' => '6296', 'sku' => ''],
        ['cod_produs' => 'DCA059', 'sku' => 'SD-010086'],
        ['cod_produs' => 'DCA066', 'sku' => 'SD-010109'],
        ['cod_produs' => 'ARH022', 'sku' => 'SD-007323'],
        ['cod_produs' => '4178', 'sku' => 'SD-001828'],
        ['cod_produs' => '2657', 'sku' => ''],
        ['cod_produs' => '3085', 'sku' => ''],
        ['cod_produs' => '5946', 'sku' => ''],
        ['cod_produs' => '2655', 'sku' => ''],
        ['cod_produs' => 'CLI005', 'sku' => 'SD-005450'],
        ['cod_produs' => '6119', 'sku' => ''],
        ['cod_produs' => '3091', 'sku' => 'SD-001719'],
        ['cod_produs' => '3090', 'sku' => 'SD-001503'],
        ['cod_produs' => '3088', 'sku' => 'SD-001504'],
        ['cod_produs' => '2793', 'sku' => ''],
        ['cod_produs' => 'PIX128', 'sku' => ''],
        ['cod_produs' => 'PIX134', 'sku' => 'SD-005522'],
        ['cod_produs' => 'PIX098', 'sku' => 'SD-005373'],
        ['cod_produs' => 'PIX041', 'sku' => ''],
        ['cod_produs' => 'PIX154', 'sku' => 'SD-007307'],
        ['cod_produs' => '2335', 'sku' => 'SD-002292'],
        ['cod_produs' => '2867', 'sku' => ''],
        ['cod_produs' => '2873', 'sku' => ''],
        ['cod_produs' => '5858', 'sku' => ''],
        ['cod_produs' => '6172', 'sku' => ''],
        ['cod_produs' => '2933', 'sku' => 'SD-001594'],
        ['cod_produs' => '6102', 'sku' => ''],
        ['cod_produs' => '3013', 'sku' => ''],
        ['cod_produs' => '4735', 'sku' => 'SD-002134'],
        ['cod_produs' => '5862', 'sku' => ''],
        ['cod_produs' => '5860', 'sku' => ''],
        ['cod_produs' => 'PGE118', 'sku' => 'SD-018915'],
        ['cod_produs' => '4581', 'sku' => ''],
        ['cod_produs' => '2934', 'sku' => ''],
        ['cod_produs' => '2936', 'sku' => ''],
        ['cod_produs' => '4195', 'sku' => ''],
        ['cod_produs' => 'ROG041', 'sku' => ''],
        ['cod_produs' => 'ROG042', 'sku' => ''],
        ['cod_produs' => 'ROG059', 'sku' => 'SD-005664'],
        ['cod_produs' => 'ROG044', 'sku' => ''],
        ['cod_produs' => '5630', 'sku' => ''],
        ['cod_produs' => '3031', 'sku' => ''],
        ['cod_produs' => '5353', 'sku' => ''],
        ['cod_produs' => '4042', 'sku' => ''],
        ['cod_produs' => '5068', 'sku' => 'SD-002291'],
        ['cod_produs' => '2922', 'sku' => 'SD-001590'],
        ['cod_produs' => 'LIN042', 'sku' => ''],
        ['cod_produs' => 'LIN044', 'sku' => 'SD-007501'],
        ['cod_produs' => 'LIN038', 'sku' => 'SD-007465'],
        ['cod_produs' => 'STI098', 'sku' => ''],
        ['cod_produs' => '2897_RV', 'sku' => ''],
        ['cod_produs' => '2844', 'sku' => ''],
        ['cod_produs' => 'ROT034', 'sku' => 'SD-018981'],
        ['cod_produs' => 'ROG092', 'sku' => 'SD-010818'],
        ['cod_produs' => '4072', 'sku' => 'SD-001524'],
        ['cod_produs' => '4037', 'sku' => 'SD-001522'],
        ['cod_produs' => 'CRM051', 'sku' => 'SD-007304'],
        ['cod_produs' => '4301', 'sku' => 'SD-001873'],
        ['cod_produs' => '4430', 'sku' => 'SD-001519'],
        ['cod_produs' => '4433', 'sku' => 'SD-001516'],
        ['cod_produs' => '4356', 'sku' => 'SD-001517'],
        ['cod_produs' => '6364', 'sku' => ''],
        ['cod_produs' => 'MKO029', 'sku' => ''],
        ['cod_produs' => '4046', 'sku' => ''],
        ['cod_produs' => '5723', 'sku' => ''],
        ['cod_produs' => 'MKO030', 'sku' => 'SD-019117'],
        ['cod_produs' => 'MKO031', 'sku' => 'SD-019180'],
        ['cod_produs' => '3030', 'sku' => ''],
        ['cod_produs' => '2927', 'sku' => ''],
        ['cod_produs' => 'MKP203', 'sku' => ''],
        ['cod_produs' => '6374', 'sku' => ''],
        ['cod_produs' => '6369', 'sku' => ''],
        ['cod_produs' => '6378', 'sku' => ''],
        ['cod_produs' => 'MKW205', 'sku' => ''],
        ['cod_produs' => '2932', 'sku' => 'SD-001593'],
        ['cod_produs' => 'MKW022', 'sku' => 'SD-010815'],
        ['cod_produs' => '2991', 'sku' => ''],
        ['cod_produs' => '3038', 'sku' => 'SD-001688'],
        ['cod_produs' => '2992', 'sku' => 'SD-004342'],
        ['cod_produs' => 'TMK058', 'sku' => ''],
        ['cod_produs' => '5511', 'sku' => 'SD-004852'],
        ['cod_produs' => '4121', 'sku' => 'SD-001482'],
        ['cod_produs' => '5512', 'sku' => 'SD-004853'],
        ['cod_produs' => 'COR068', 'sku' => 'SD-008817'],
        ['cod_produs' => '6454', 'sku' => 'SD-003846'],
        ['cod_produs' => '6455', 'sku' => 'SD-003847'],
        ['cod_produs' => 'HCO117', 'sku' => 'SD-008559'],
        ['cod_produs' => 'HCO118', 'sku' => 'SD-008562'],
        ['cod_produs' => 'HCO122', 'sku' => 'SD-008563'],
        ['cod_produs' => 'HCO126', 'sku' => 'SD-008566'],
        ['cod_produs' => 'HCO128', 'sku' => 'SD-008329'],
        ['cod_produs' => 'HCO129', 'sku' => 'SD-008569'],
        ['cod_produs' => 'HCO200', 'sku' => ''],
        ['cod_produs' => 'HCO230', 'sku' => ''],
        ['cod_produs' => 'HCO013', 'sku' => ''],
        ['cod_produs' => 'HCO015', 'sku' => ''],
        ['cod_produs' => 'NOT072', 'sku' => ''],
        ['cod_produs' => 'HCO017', 'sku' => 'SD-008604'],
        ['cod_produs' => 'HCO079', 'sku' => 'SD-008601'],
        ['cod_produs' => 'NOT205', 'sku' => 'SD-009762'],
        ['cod_produs' => 'NOT036', 'sku' => 'SD-005355'],
        ['cod_produs' => 'NOT028', 'sku' => 'SD-005286'],
        ['cod_produs' => 'NOT079', 'sku' => 'SD-005383'],
        ['cod_produs' => 'NOT082', 'sku' => 'SD-005386'],
        ['cod_produs' => '2831', 'sku' => 'SD-001558'],
        ['cod_produs' => '5533', 'sku' => 'SD-003115'],
        ['cod_produs' => '5534', 'sku' => 'SD-003116'],
        ['cod_produs' => '5529', 'sku' => 'SD-003111'],
        ['cod_produs' => '2826', 'sku' => 'SD-001555'],
        ['cod_produs' => '3040', 'sku' => 'SD-001689'],
        ['cod_produs' => 'NOT086', 'sku' => 'SD-007300'],
        ['cod_produs' => '2559', 'sku' => ''],
        ['cod_produs' => '6435', 'sku' => ''],
        ['cod_produs' => '6156', 'sku' => ''],
        ['cod_produs' => '5739', 'sku' => ''],
        ['cod_produs' => 'CAI077', 'sku' => 'SD-008430'],
        ['cod_produs' => 'CAI076', 'sku' => 'SD-007847'],
        ['cod_produs' => 'CAI052', 'sku' => 'SD-006907'],
        ['cod_produs' => '4177', 'sku' => ''],
        ['cod_produs' => '2674', 'sku' => 'SD-000140'],
        ['cod_produs' => 'NOT021', 'sku' => 'SD-006771'],
        ['cod_produs' => 'COL017', 'sku' => 'SD-005585'],
        ['cod_produs' => '2724', 'sku' => ''],
        ['cod_produs' => '2686', 'sku' => 'SD-000144'],
        ['cod_produs' => 'FOA030', 'sku' => 'SD-006212'],
        ['cod_produs' => '4323', 'sku' => 'SD-001881'],
        ['cod_produs' => '6145', 'sku' => ''],
        ['cod_produs' => '6173', 'sku' => ''],
        ['cod_produs' => '6141', 'sku' => 'SD-001450'],
        ['cod_produs' => 'CAI006', 'sku' => ''],
        ['cod_produs' => 'CAI071', 'sku' => ''],
        ['cod_produs' => 'CAI055', 'sku' => ''],
        ['cod_produs' => 'CAI041', 'sku' => 'SD-006898'],
        ['cod_produs' => 'CAI043', 'sku' => 'SD-006900'],
        ['cod_produs' => 'REG002', 'sku' => 'SD-006913'],
        ['cod_produs' => '6175', 'sku' => 'SD-003625'],
        ['cod_produs' => '2730', 'sku' => 'SD-000325'],
        ['cod_produs' => 'SKO503', 'sku' => 'SD-019683'],
        ['cod_produs' => 'SKO010', 'sku' => 'SD-005634'],
        ['cod_produs' => '2662', 'sku' => 'SD-000136'],
        ['cod_produs' => 'SKR095', 'sku' => 'SD-008495'],
        ['cod_produs' => 'SKR096', 'sku' => 'SD-008496'],
        ['cod_produs' => '4304', 'sku' => 'SD-001876'],
        ['cod_produs' => '4303', 'sku' => 'SD-001875'],
        ['cod_produs' => '6405', 'sku' => 'SD-003815'],
        ['cod_produs' => '2967', 'sku' => ''],
        ['cod_produs' => '6154', 'sku' => 'SD-003594'],
        ['cod_produs' => '4018', 'sku' => 'SD-001727'],
        ['cod_produs' => '4016', 'sku' => ''],
        ['cod_produs' => '6398', 'sku' => ''],
        ['cod_produs' => '2800', 'sku' => 'SD-001549'],
        ['cod_produs' => '6223', 'sku' => ''],
        ['cod_produs' => 'TAV024', 'sku' => 'SD-007812'],
        ['cod_produs' => '2664', 'sku' => 'SD-001326'],
        ['cod_produs' => 'CPS133', 'sku' => ''],
        ['cod_produs' => '6341', 'sku' => ''],
        ['cod_produs' => '6338', 'sku' => 'SD-003786'],
        ['cod_produs' => '6337', 'sku' => ''],
        ['cod_produs' => '6151', 'sku' => 'SD-001506'],
        ['cod_produs' => 'CPS023', 'sku' => 'SD-008307'],
        ['cod_produs' => '6343', 'sku' => 'SD-004868'],
        ['cod_produs' => '6344', 'sku' => 'SD-004869'],
        ['cod_produs' => '6345', 'sku' => 'SD-004870'],
        ['cod_produs' => 'CPA071', 'sku' => 'SD-019067'],
        ['cod_produs' => '6348', 'sku' => 'SD-004909'],
        ['cod_produs' => 'CPA072', 'sku' => 'SD-019068'],
        ['cod_produs' => '6350', 'sku' => 'SD-004910'],
        ['cod_produs' => '6362', 'sku' => 'SD-003798'],
        ['cod_produs' => '6351', 'sku' => ''],
        ['cod_produs' => 'PER042', 'sku' => ''],
        ['cod_produs' => '5496', 'sku' => 'SD-003081'],
        ['cod_produs' => '2571', 'sku' => 'SD-000095'],
        ['cod_produs' => 'PER011', 'sku' => 'SD-008386'],
        ['cod_produs' => 'BAD023', 'sku' => 'SD-005302'],
        ['cod_produs' => 'BAD081', 'sku' => 'SD-005300'],
        ['cod_produs' => '2568', 'sku' => 'SD-000092'],
        ['cod_produs' => '2696', 'sku' => 'SD-000149'],
        ['cod_produs' => '5876', 'sku' => 'SD-003397'],
        ['cod_produs' => '5509', 'sku' => 'SD-003091'],
        ['cod_produs' => '5542', 'sku' => 'SD-003124'],
        ['cod_produs' => '2562', 'sku' => 'SD-000088'],
        ['cod_produs' => '6391', 'sku' => 'SD-003806'],
        ['cod_produs' => '5545', 'sku' => 'SD-001367'],
        ['cod_produs' => 'WHB066', 'sku' => ''],
        ['cod_produs' => 'CPC009', 'sku' => 'SD-006807'],
        ['cod_produs' => 'CPC005', 'sku' => 'SD-007754'],
        ['cod_produs' => 'REZ078', 'sku' => ''],
        ['cod_produs' => 'TMK036', 'sku' => ''],
        ['cod_produs' => 'CER034', 'sku' => 'SD-011849'],
        ['cod_produs' => 'CRE053', 'sku' => 'SD-011701'],
        ['cod_produs' => 'COR061', 'sku' => 'SD-008759'],
        ['cod_produs' => 'CPA053', 'sku' => 'SD-008859'],
        ['cod_produs' => 'FOA046', 'sku' => 'SD-008801'],
        ['cod_produs' => 'CRM056', 'sku' => 'SD-008857'],
        ['cod_produs' => 'LAM024', 'sku' => 'SD-011996'],
        ['cod_produs' => 'LAM028', 'sku' => 'SD-012000'],
        ['cod_produs' => 'PIX195', 'sku' => ''],
        ['cod_produs' => 'LIP217', 'sku' => 'SD-010536'],
        ['cod_produs' => 'SKR159', 'sku' => 'SD-012196'],
        ['cod_produs' => 'SKR162', 'sku' => 'SD-012193'],
        ['cod_produs' => 'CAI200', 'sku' => ''],
        ['cod_produs' => 'CAI201', 'sku' => ''],
        ['cod_produs' => 'SKE040', 'sku' => ''],
        ['cod_produs' => 'CAI193', 'sku' => 'SD-012089'],
        ['cod_produs' => '5958_RV', 'sku' => ''],
        ['cod_produs' => '4679_RV', 'sku' => ''],
        ['cod_produs' => '4137_RV', 'sku' => ''],
        ['cod_produs' => '2978_RV', 'sku' => ''],
        ['cod_produs' => 'TMK039', 'sku' => 'SD-012551'],
        ['cod_produs' => '2891_RV', 'sku' => ''],
        ['cod_produs' => 'CAI163', 'sku' => ''],
        ['cod_produs' => 'CAI181', 'sku' => ''],
        ['cod_produs' => 'CAI056', 'sku' => 'SD-010996'],
        ['cod_produs' => 'CAI158', 'sku' => 'SD-010902'],
        ['cod_produs' => 'CAI162', 'sku' => 'SD-010941'],
        ['cod_produs' => 'CAI016', 'sku' => 'SD-010129'],
        ['cod_produs' => 'CAI170', 'sku' => 'SD-011089'],
        ['cod_produs' => 'CAI168', 'sku' => 'SD-011081'],
        ['cod_produs' => 'CAI173', 'sku' => 'SD-011092'],
        ['cod_produs' => 'CAI165', 'sku' => 'SD-010946'],
        ['cod_produs' => 'CAI194', 'sku' => 'SD-012090'],
        ['cod_produs' => 'HCO367', 'sku' => ''],
        ['cod_produs' => 'SRE021', 'sku' => ''],
        ['cod_produs' => 'SKR192', 'sku' => 'SD-012250'],
        ['cod_produs' => 'AGE350', 'sku' => 'SD-010994'],
        ['cod_produs' => 'CAI178', 'sku' => 'SD-011588'],
        ['cod_produs' => 'CAI195', 'sku' => 'SD-012091'],
        ['cod_produs' => 'HCO362', 'sku' => 'SD-011077'],
        ['cod_produs' => 'CRM068', 'sku' => ''],
        ['cod_produs' => 'ARH027', 'sku' => 'SD-012689'],
        ['cod_produs' => 'CER035', 'sku' => ''],
        ['cod_produs' => 'REZ082', 'sku' => 'SD-013076'],
        ['cod_produs' => 'CAI250', 'sku' => ''],
        ['cod_produs' => 'CAI245', 'sku' => ''],
        ['cod_produs' => 'CAI238', 'sku' => ''],
        ['cod_produs' => 'CAI239', 'sku' => ''],
        ['cod_produs' => 'CAI248', 'sku' => ''],
        ['cod_produs' => 'CAI249', 'sku' => ''],
        ['cod_produs' => 'CAI230', 'sku' => ''],
        ['cod_produs' => 'PIX523', 'sku' => 'SD-012700'],
        ['cod_produs' => 'CMD007', 'sku' => 'SD-013366'],
        ['cod_produs' => 'MLW615', 'sku' => ''],
        ['cod_produs' => 'MLW616', 'sku' => ''],
        ['cod_produs' => 'MLW617', 'sku' => ''],
        ['cod_produs' => 'MLW618', 'sku' => ''],
        ['cod_produs' => 'MLW619', 'sku' => ''],
        ['cod_produs' => 'MLW620', 'sku' => ''],
        ['cod_produs' => 'MLW621', 'sku' => ''],
        ['cod_produs' => 'MLW622', 'sku' => ''],
        ['cod_produs' => 'MLW623', 'sku' => ''],
        ['cod_produs' => 'MLW624', 'sku' => ''],
        ['cod_produs' => 'MLW625', 'sku' => ''],
        ['cod_produs' => 'MLW719', 'sku' => 'SD-011923'],
        ['cod_produs' => 'MLW733', 'sku' => 'SD-012802'],
        ['cod_produs' => 'MLW734', 'sku' => 'SD-012803'],
        ['cod_produs' => 'MLW613', 'sku' => 'SD-010317'],
        ['cod_produs' => 'MLW614', 'sku' => 'SD-010318'],
        ['cod_produs' => 'MLW732', 'sku' => 'SD-012801'],
        ['cod_produs' => 'MLW612', 'sku' => 'SD-010316'],
        ['cod_produs' => 'MLW730', 'sku' => 'SD-012787'],
        ['cod_produs' => 'MLW729', 'sku' => 'SD-012782'],
        ['cod_produs' => 'MLW731', 'sku' => 'SD-012786'],
        ['cod_produs' => 'MLW478', 'sku' => 'SD-009809'],
        ['cod_produs' => 'MLW556', 'sku' => 'SD-009794'],
        ['cod_produs' => 'MLW555', 'sku' => 'SD-009793'],
        ['cod_produs' => 'MLW475', 'sku' => 'SD-009790'],
        ['cod_produs' => 'MLW553', 'sku' => 'SD-009795'],
        ['cod_produs' => 'MLW476', 'sku' => 'SD-009807'],
        ['cod_produs' => 'MLW477', 'sku' => 'SD-009808'],
        ['cod_produs' => 'MLW554', 'sku' => 'SD-009792'],
        ['cod_produs' => 'CAI190', 'sku' => ''],
        ['cod_produs' => 'CAI192', 'sku' => ''],
        ['cod_produs' => 'SKR223', 'sku' => 'SD-013661'],
        ['cod_produs' => 'PIX233', 'sku' => 'SD-013860'],
        ['cod_produs' => 'STI162', 'sku' => ''],
        ['cod_produs' => 'STI163', 'sku' => ''],
        ['cod_produs' => 'STI164', 'sku' => ''],
        ['cod_produs' => 'LIN061', 'sku' => ''],
        ['cod_produs' => 'LIN062', 'sku' => ''],
        ['cod_produs' => 'LIN063', 'sku' => ''],
        ['cod_produs' => 'LIN064', 'sku' => ''],
        ['cod_produs' => 'LIN065', 'sku' => ''],
        ['cod_produs' => 'LIN066', 'sku' => ''],
        ['cod_produs' => 'LIN067', 'sku' => ''],
        ['cod_produs' => 'LIN068', 'sku' => ''],
        ['cod_produs' => 'LIN071', 'sku' => 'SD-013820'],
        ['cod_produs' => 'LIN070', 'sku' => 'SD-013819'],
        ['cod_produs' => 'LIN072', 'sku' => 'SD-013821'],
        ['cod_produs' => 'REZ088', 'sku' => ''],
        ['cod_produs' => 'CER037', 'sku' => ''],
        ['cod_produs' => 'RAC592', 'sku' => ''],
        ['cod_produs' => 'RAC595', 'sku' => ''],
        ['cod_produs' => 'RAC596', 'sku' => ''],
        ['cod_produs' => 'RAC600', 'sku' => ''],
        ['cod_produs' => 'RAC601', 'sku' => ''],
        ['cod_produs' => 'RAC599', 'sku' => ''],
        ['cod_produs' => 'RIG326', 'sku' => 'SD-014637'],
        ['cod_produs' => 'RIG347', 'sku' => 'SD-014630'],
        ['cod_produs' => 'ROG159', 'sku' => ''],
        ['cod_produs' => 'PMK026', 'sku' => 'SD-015069'],
        ['cod_produs' => 'PMK027', 'sku' => 'SD-015070'],
        ['cod_produs' => 'PMK023', 'sku' => 'SD-015066'],
        ['cod_produs' => 'PMK024', 'sku' => 'SD-015067'],
        ['cod_produs' => 'SKR213', 'sku' => 'SD-013601'],
        ['cod_produs' => 'PMK044', 'sku' => ''],
        ['cod_produs' => 'PMK045', 'sku' => ''],
        ['cod_produs' => 'PMK046', 'sku' => ''],
        ['cod_produs' => 'PMK048', 'sku' => ''],
        ['cod_produs' => 'PMK049', 'sku' => ''],
        ['cod_produs' => 'PMK050', 'sku' => ''],
        ['cod_produs' => 'PMK042', 'sku' => 'SD-015085'],
        ['cod_produs' => 'PMK032', 'sku' => ''],
        ['cod_produs' => 'PMK033', 'sku' => ''],
        ['cod_produs' => 'PMK034', 'sku' => ''],
        ['cod_produs' => 'PMK035', 'sku' => ''],
        ['cod_produs' => 'PMK036', 'sku' => ''],
        ['cod_produs' => 'PMK037', 'sku' => ''],
        ['cod_produs' => 'PMK038', 'sku' => ''],
        ['cod_produs' => 'PMK040', 'sku' => 'SD-015083'],
        ['cod_produs' => 'PMK059', 'sku' => ''],
        ['cod_produs' => 'PMK058', 'sku' => ''],
        ['cod_produs' => 'PMK055', 'sku' => ''],
        ['cod_produs' => 'PMK060', 'sku' => ''],
        ['cod_produs' => 'PMK056', 'sku' => ''],
        ['cod_produs' => 'PMK062', 'sku' => ''],
        ['cod_produs' => 'PMK061', 'sku' => ''],
        ['cod_produs' => 'PMK052', 'sku' => 'SD-015095'],
        ['cod_produs' => 'CPC034', 'sku' => 'SD-015033'],
        ['cod_produs' => 'CPC035', 'sku' => 'SD-015034'],
        ['cod_produs' => 'CPC027', 'sku' => 'SD-015002'],
        ['cod_produs' => 'MIC219', 'sku' => 'SD-008417'],
        ['cod_produs' => 'CAI135', 'sku' => 'SD-010667'],
        ['cod_produs' => 'CAI171', 'sku' => 'SD-011090'],
        ['cod_produs' => 'CAI183', 'sku' => 'SD-011595'],
        ['cod_produs' => 'AGE358', 'sku' => 'SD-011078'],
        ['cod_produs' => 'CAI172', 'sku' => 'SD-011091'],
        ['cod_produs' => 'CAI107', 'sku' => 'SD-007089'],
        ['cod_produs' => 'CAI179', 'sku' => 'SD-011590'],
        ['cod_produs' => 'HCO081', 'sku' => 'SD-011344'],
        ['cod_produs' => 'CAI017', 'sku' => 'SD-006906'],
        ['cod_produs' => 'SKG112', 'sku' => 'SD-015257'],
        ['cod_produs' => 'SKG090', 'sku' => 'SD-015219'],
        ['cod_produs' => 'SKG129', 'sku' => 'SD-015436'],
        ['cod_produs' => 'SKG127', 'sku' => 'SD-015434'],
        ['cod_produs' => 'CAI255', 'sku' => ''],
        ['cod_produs' => 'BAD085', 'sku' => 'SD-015191'],
        ['cod_produs' => 'BAD087', 'sku' => 'SD-015193'],
        ['cod_produs' => 'NOT110', 'sku' => 'SD-015635'],
        ['cod_produs' => 'COR086', 'sku' => 'SD-015775'],
        ['cod_produs' => 'PMK069', 'sku' => 'SD-015790'],
        ['cod_produs' => 'PMK070', 'sku' => 'SD-015791'],
        ['cod_produs' => 'SKG147', 'sku' => 'SD-015728'],
        ['cod_produs' => 'SKG154', 'sku' => 'SD-015971'],
        ['cod_produs' => 'PGE111', 'sku' => ''],
        ['cod_produs' => 'PGE112', 'sku' => ''],
        ['cod_produs' => 'CRM112', 'sku' => 'SD-015959'],
        ['cod_produs' => 'PIX540', 'sku' => ''],
        ['cod_produs' => 'BLD015', 'sku' => 'SD-016035'],
        ['cod_produs' => 'STI177', 'sku' => ''],
        ['cod_produs' => 'CAL035', 'sku' => 'SD-016068'],
        ['cod_produs' => 'CAL038', 'sku' => 'SD-016071'],
        ['cod_produs' => 'CAL041', 'sku' => 'SD-016074'],
        ['cod_produs' => 'CAL044', 'sku' => 'SD-016080'],
        ['cod_produs' => 'CAL047', 'sku' => 'SD-016083'],
        ['cod_produs' => 'CAL052', 'sku' => 'SD-016088'],
        ['cod_produs' => 'CAL054', 'sku' => 'SD-016090'],
        ['cod_produs' => 'SKG178', 'sku' => 'SD-016212'],
        ['cod_produs' => 'SKG175', 'sku' => 'SD-016209'],
        ['cod_produs' => 'SKG171', 'sku' => 'SD-016205'],
        ['cod_produs' => 'SKG174', 'sku' => 'SD-016208'],
        ['cod_produs' => 'SKG173', 'sku' => 'SD-016207'],
        ['cod_produs' => 'SKG162', 'sku' => 'SD-016196'],
        ['cod_produs' => 'SKG168', 'sku' => 'SD-016202'],
        ['cod_produs' => 'NOT130', 'sku' => 'SD-018951'],
        ['cod_produs' => 'PIX029', 'sku' => ''],
        ['cod_produs' => 'PIX545', 'sku' => ''],
        ['cod_produs' => 'SKG192', 'sku' => 'SD-016234'],
        ['cod_produs' => 'SKG205', 'sku' => 'SD-016242'],
        ['cod_produs' => 'SKG195', 'sku' => 'SD-016237'],
        ['cod_produs' => 'SKG196', 'sku' => 'SD-016238'],
        ['cod_produs' => 'SKG184', 'sku' => 'SD-016226'],
        ['cod_produs' => 'SKG187', 'sku' => 'SD-016229'],
        ['cod_produs' => 'SKG207', 'sku' => 'SD-016244'],
        ['cod_produs' => 'SKG206', 'sku' => 'SD-016243'],
        ['cod_produs' => 'SKE077', 'sku' => 'SD-016251'],
        ['cod_produs' => 'SKP182', 'sku' => 'SD-016633'],
        ['cod_produs' => 'REZ070', 'sku' => ''],
        ['cod_produs' => 'PIX161', 'sku' => 'SD-008854'],
        ['cod_produs' => 'CLI002', 'sku' => ''],
        ['cod_produs' => 'NBK101', 'sku' => ''],
        ['cod_produs' => 'DIS093', 'sku' => 'SD-016277'],
        ['cod_produs' => 'DIS109', 'sku' => 'SD-016278'],
        ['cod_produs' => 'NBK100', 'sku' => 'SD-016703'],
        ['cod_produs' => 'TIP965', 'sku' => 'SD-016720'],
        ['cod_produs' => 'TIP967', 'sku' => 'SD-016722'],
        ['cod_produs' => 'STI180', 'sku' => ''],
        ['cod_produs' => 'SKP074', 'sku' => 'SD-016404'],
        ['cod_produs' => 'SKP173', 'sku' => 'SD-016576'],
        ['cod_produs' => 'SKP172', 'sku' => 'SD-016575'],
        ['cod_produs' => 'SKP087', 'sku' => 'SD-016417'],
        ['cod_produs' => 'SKP088', 'sku' => 'SD-016416'],
        ['cod_produs' => 'SKP091', 'sku' => 'SD-016413'],
        ['cod_produs' => 'SKP092', 'sku' => 'SD-016412'],
        ['cod_produs' => 'SKP093', 'sku' => 'SD-016411'],
        ['cod_produs' => 'SKP094', 'sku' => 'SD-016434'],
        ['cod_produs' => 'SKP096', 'sku' => 'SD-016435'],
        ['cod_produs' => 'SKP097', 'sku' => 'SD-016436'],
        ['cod_produs' => 'SKP104', 'sku' => 'SD-016443'],
        ['cod_produs' => 'SKP105', 'sku' => 'SD-016444'],
        ['cod_produs' => 'SKP106', 'sku' => 'SD-016445'],
        ['cod_produs' => 'SKP107', 'sku' => 'SD-016446'],
        ['cod_produs' => 'SKP086', 'sku' => 'SD-016418'],
        ['cod_produs' => 'PIX546', 'sku' => ''],
        ['cod_produs' => 'SKP191', 'sku' => 'SD-016895'],
        ['cod_produs' => 'MKP056', 'sku' => ''],
        ['cod_produs' => 'MKP059', 'sku' => ''],
        ['cod_produs' => 'SKP196', 'sku' => 'SD-016900'],
        ['cod_produs' => 'MLW001', 'sku' => 'SD-009272'],
        ['cod_produs' => 'MLW144', 'sku' => 'SD-009415'],
        ['cod_produs' => 'MLW205', 'sku' => 'SD-009495'],
        ['cod_produs' => 'MLW301', 'sku' => 'SD-009596'],
        ['cod_produs' => 'MLW463', 'sku' => 'SD-009772'],
        ['cod_produs' => 'MLW466', 'sku' => 'SD-009775'],
        ['cod_produs' => 'MLW471', 'sku' => 'SD-009786'],
        ['cod_produs' => 'MLW470', 'sku' => 'SD-009779'],
        ['cod_produs' => 'MLW459', 'sku' => 'SD-009768'],
        ['cod_produs' => 'MLW467', 'sku' => 'SD-009776'],
        ['cod_produs' => 'MLW754', 'sku' => 'SD-016012'],
        ['cod_produs' => 'SKG225', 'sku' => 'SD-017348'],
        ['cod_produs' => 'SKG227', 'sku' => 'SD-017353'],
        ['cod_produs' => 'SKG228', 'sku' => 'SD-017358'],
        ['cod_produs' => 'SKG230', 'sku' => 'SD-017363'],
        ['cod_produs' => 'SKG231', 'sku' => 'SD-017364'],
        ['cod_produs' => 'SKG232', 'sku' => 'SD-017365'],
        ['cod_produs' => 'MLW766', 'sku' => ''],
        ['cod_produs' => 'MLW755', 'sku' => ''],
        ['cod_produs' => 'MLW756', 'sku' => ''],
        ['cod_produs' => 'MLW757', 'sku' => ''],
        ['cod_produs' => 'MLW758', 'sku' => ''],
        ['cod_produs' => 'MLW759', 'sku' => ''],
        ['cod_produs' => 'MLW760', 'sku' => ''],
        ['cod_produs' => 'MLW761', 'sku' => ''],
        ['cod_produs' => 'MLW762', 'sku' => ''],
        ['cod_produs' => 'MLW763', 'sku' => ''],
        ['cod_produs' => 'MLW764', 'sku' => ''],
        ['cod_produs' => 'MLW765', 'sku' => ''],
        ['cod_produs' => 'SKP245', 'sku' => ''],
        ['cod_produs' => 'STI189', 'sku' => ''],
        ['cod_produs' => 'STI195', 'sku' => ''],
        ['cod_produs' => 'SKR259', 'sku' => 'SD-017519'],
        ['cod_produs' => 'SKG233', 'sku' => 'SD-017561'],
        ['cod_produs' => 'SKG234', 'sku' => 'SD-017562'],
        ['cod_produs' => 'SKG236', 'sku' => 'SD-017564'],
        ['cod_produs' => 'SKG239', 'sku' => 'SD-017567'],
        ['cod_produs' => 'SKG240', 'sku' => 'SD-017568'],
        ['cod_produs' => 'SKG242', 'sku' => 'SD-017570'],
        ['cod_produs' => 'NBK664', 'sku' => 'SD-017594'],
        ['cod_produs' => 'NBK665', 'sku' => 'SD-017595'],
        ['cod_produs' => 'NBK666', 'sku' => 'SD-017596'],
        ['cod_produs' => 'NBK667', 'sku' => 'SD-017597'],
        ['cod_produs' => 'NBK668', 'sku' => 'SD-017598'],
        ['cod_produs' => 'NBK669', 'sku' => 'SD-017599'],
        ['cod_produs' => 'NBK671', 'sku' => 'SD-017601'],
        ['cod_produs' => 'NBK670', 'sku' => 'SD-017600'],
        ['cod_produs' => 'NBK672', 'sku' => 'SD-017602'],
        ['cod_produs' => 'NBK673', 'sku' => 'SD-017603'],
        ['cod_produs' => 'NBK674', 'sku' => 'SD-017605'],
        ['cod_produs' => 'NBK675', 'sku' => 'SD-017607'],
        ['cod_produs' => 'NBK676', 'sku' => 'SD-017614'],
        ['cod_produs' => 'NBK677', 'sku' => 'SD-017615'],
        ['cod_produs' => 'NBK678', 'sku' => 'SD-017616'],
        ['cod_produs' => 'NBK679', 'sku' => 'SD-017617'],
        ['cod_produs' => 'NBK680', 'sku' => 'SD-017618'],
        ['cod_produs' => 'NBK681', 'sku' => 'SD-017634'],
        ['cod_produs' => 'NBK682', 'sku' => 'SD-017635'],
        ['cod_produs' => 'NBK683', 'sku' => 'SD-017636'],
        ['cod_produs' => 'NBK684', 'sku' => 'SD-017637'],
        ['cod_produs' => 'NBK685', 'sku' => 'SD-017638'],
        ['cod_produs' => 'NBK686', 'sku' => 'SD-017640'],
        ['cod_produs' => 'NBK687', 'sku' => 'SD-017641'],
        ['cod_produs' => 'NBK688', 'sku' => 'SD-017642'],
        ['cod_produs' => 'NBK689', 'sku' => 'SD-017643'],
        ['cod_produs' => 'NBK690', 'sku' => 'SD-017644'],
        ['cod_produs' => 'NBK691', 'sku' => 'SD-017654'],
        ['cod_produs' => 'NBK692', 'sku' => 'SD-017655'],
        ['cod_produs' => 'NBK693', 'sku' => 'SD-017656'],
        ['cod_produs' => 'NBK694', 'sku' => 'SD-017657'],
        ['cod_produs' => 'NBK695', 'sku' => 'SD-017658'],
        ['cod_produs' => 'NBK696', 'sku' => 'SD-017659'],
        ['cod_produs' => 'NBK697', 'sku' => 'SD-017660'],
        ['cod_produs' => 'NBK698', 'sku' => 'SD-017661'],
        ['cod_produs' => 'NBK699', 'sku' => 'SD-017663'],
        ['cod_produs' => 'NBK700', 'sku' => 'SD-017678'],
        ['cod_produs' => 'NBK701', 'sku' => 'SD-017679'],
        ['cod_produs' => 'NBK702', 'sku' => 'SD-017680'],
        ['cod_produs' => 'NBK703', 'sku' => 'SD-017681'],
        ['cod_produs' => 'PIX242', 'sku' => 'SD-017709'],
        ['cod_produs' => 'SKG249', 'sku' => 'SD-017871'],
        ['cod_produs' => 'SKG250', 'sku' => 'SD-017872'],
        ['cod_produs' => 'STI138_RV', 'sku' => ''],
        ['cod_produs' => 'STI196', 'sku' => ''],
        ['cod_produs' => 'SKG258', 'sku' => 'SD-018021'],
        ['cod_produs' => 'SKG259', 'sku' => 'SD-018022'],
        ['cod_produs' => 'SKG260', 'sku' => 'SD-018023'],
        ['cod_produs' => 'CAL242', 'sku' => 'SD-018112'],
        ['cod_produs' => 'REZ077', 'sku' => ''],
        ['cod_produs' => 'APC734', 'sku' => ''],
        ['cod_produs' => '2587', 'sku' => 'SD-000120'],
        ['cod_produs' => 'SKG255', 'sku' => 'SD-017877'],
        ['cod_produs' => 'MOB379', 'sku' => 'SD-013692'],
        ['cod_produs' => 'MOB428', 'sku' => 'SD-013695'],
        ['cod_produs' => 'MOB429', 'sku' => 'SD-013698'],
        ['cod_produs' => 'REZ073', 'sku' => ''],
        ['cod_produs' => 'REZ075', 'sku' => ''],
        ['cod_produs' => '6169', 'sku' => 'SD-003617'],
        ['cod_produs' => 'SKE089', 'sku' => ''],
        ['cod_produs' => 'TLL611292', 'sku' => 'SD-018370'],
        ['cod_produs' => 'TLL611302', 'sku' => 'SD-018473'],
        ['cod_produs' => 'TLL611312', 'sku' => 'SD-018474'],
        ['cod_produs' => 'TLL611202', 'sku' => 'SD-018477'],
        ['cod_produs' => 'TLL611232', 'sku' => 'SD-018479'],
        ['cod_produs' => 'TLL611222', 'sku' => 'SD-018475'],
        ['cod_produs' => 'TLL193011', 'sku' => 'SD-018481'],
        ['cod_produs' => 'TLL193021', 'sku' => 'SD-018482'],
        ['cod_produs' => 'TLL312021', 'sku' => 'SD-018593'],
        ['cod_produs' => 'TLL151391', 'sku' => 'SD-018608'],
        ['cod_produs' => 'TLL151371', 'sku' => 'SD-018610'],
        ['cod_produs' => 'TLL193001', 'sku' => 'SD-018622'],
        ['cod_produs' => 'TLL158451', 'sku' => 'SD-018662'],
        ['cod_produs' => 'ARH269', 'sku' => 'SD-018835'],
        ['cod_produs' => 'RAC1047', 'sku' => 'SD-018814'],
        ['cod_produs' => 'RAC1049', 'sku' => 'SD-018816'],
        ['cod_produs' => 'RAC1050', 'sku' => 'SD-018817'],
        ['cod_produs' => 'RAC1059', 'sku' => 'SD-018826'],
        ['cod_produs' => 'SKG271', 'sku' => 'SD-018795'],
        ['cod_produs' => 'SKG273', 'sku' => 'SD-018799'],
        ['cod_produs' => 'SKG277', 'sku' => 'SD-018827'],
        ['cod_produs' => 'SKG279', 'sku' => 'SD-018829'],
        ['cod_produs' => 'SKG280', 'sku' => 'SD-018830'],
        ['cod_produs' => 'SKG291', 'sku' => 'SD-018845'],
        ['cod_produs' => 'SKG292', 'sku' => 'SD-018846'],
        ['cod_produs' => 'SKG282', 'sku' => 'SD-018836'],
        ['cod_produs' => 'SKG284', 'sku' => 'SD-018838'],
        ['cod_produs' => 'SKG286', 'sku' => 'SD-018839'],
        ['cod_produs' => 'SKG289', 'sku' => 'SD-018843'],
        ['cod_produs' => 'SKG290', 'sku' => 'SD-018844'],
        ['cod_produs' => 'SKG283', 'sku' => 'SD-018837'],
        ['cod_produs' => 'SKG285', 'sku' => 'SD-018840'],
        ['cod_produs' => 'SKG287', 'sku' => 'SD-018841'],
        ['cod_produs' => 'SKG288', 'sku' => 'SD-018842'],
        ['cod_produs' => 'MLW002', 'sku' => ''],
        ['cod_produs' => 'MLW003', 'sku' => ''],
        ['cod_produs' => 'MLW004', 'sku' => ''],
        ['cod_produs' => 'MLW005', 'sku' => ''],
        ['cod_produs' => 'MLW006', 'sku' => ''],
        ['cod_produs' => 'MLW007', 'sku' => ''],
        ['cod_produs' => 'MLW008', 'sku' => ''],
        ['cod_produs' => 'MLW009', 'sku' => ''],
        ['cod_produs' => 'MLW010', 'sku' => ''],
        ['cod_produs' => 'MLW011', 'sku' => ''],
        ['cod_produs' => 'MLW012', 'sku' => ''],
        ['cod_produs' => 'MLW013', 'sku' => ''],
        ['cod_produs' => 'MLW014', 'sku' => ''],
        ['cod_produs' => 'MLW015', 'sku' => ''],
        ['cod_produs' => 'MLW016', 'sku' => ''],
        ['cod_produs' => 'MLW017', 'sku' => ''],
        ['cod_produs' => 'MLW018', 'sku' => ''],
        ['cod_produs' => 'MLW019', 'sku' => ''],
        ['cod_produs' => 'MLW020', 'sku' => ''],
        ['cod_produs' => 'MLW021', 'sku' => ''],
        ['cod_produs' => 'MLW022', 'sku' => ''],
        ['cod_produs' => 'MLW023', 'sku' => ''],
        ['cod_produs' => 'MLW024', 'sku' => ''],
        ['cod_produs' => 'MLW025', 'sku' => ''],
        ['cod_produs' => 'MLW026', 'sku' => ''],
        ['cod_produs' => 'MLW027', 'sku' => ''],
        ['cod_produs' => 'MLW028', 'sku' => ''],
        ['cod_produs' => 'MLW029', 'sku' => ''],
        ['cod_produs' => 'MLW030', 'sku' => ''],
        ['cod_produs' => 'MLW031', 'sku' => ''],
        ['cod_produs' => 'MLW032', 'sku' => ''],
        ['cod_produs' => 'MLW033', 'sku' => ''],
        ['cod_produs' => 'MLW034', 'sku' => ''],
        ['cod_produs' => 'MLW035', 'sku' => ''],
        ['cod_produs' => 'MLW036', 'sku' => ''],
        ['cod_produs' => 'MLW037', 'sku' => ''],
        ['cod_produs' => 'MLW038', 'sku' => ''],
        ['cod_produs' => 'MLW039', 'sku' => ''],
        ['cod_produs' => 'MLW040', 'sku' => ''],
        ['cod_produs' => 'MLW041', 'sku' => ''],
        ['cod_produs' => 'MLW042', 'sku' => ''],
        ['cod_produs' => 'MLW043', 'sku' => ''],
        ['cod_produs' => 'MLW044', 'sku' => ''],
        ['cod_produs' => 'MLW045', 'sku' => ''],
        ['cod_produs' => 'MLW046', 'sku' => ''],
        ['cod_produs' => 'MLW047', 'sku' => ''],
        ['cod_produs' => 'MLW048', 'sku' => ''],
        ['cod_produs' => 'MLW049', 'sku' => ''],
        ['cod_produs' => 'MLW050', 'sku' => ''],
        ['cod_produs' => 'MLW051', 'sku' => ''],
        ['cod_produs' => 'MLW052', 'sku' => ''],
        ['cod_produs' => 'MLW053', 'sku' => ''],
        ['cod_produs' => 'MLW054', 'sku' => ''],
        ['cod_produs' => 'MLW055', 'sku' => ''],
        ['cod_produs' => 'MLW056', 'sku' => ''],
        ['cod_produs' => 'MLW057', 'sku' => ''],
        ['cod_produs' => 'MLW058', 'sku' => ''],
        ['cod_produs' => 'MLW059', 'sku' => ''],
        ['cod_produs' => 'MLW060', 'sku' => ''],
        ['cod_produs' => 'MLW061', 'sku' => ''],
        ['cod_produs' => 'MLW062', 'sku' => ''],
        ['cod_produs' => 'MLW063', 'sku' => ''],
        ['cod_produs' => 'MLW064', 'sku' => ''],
        ['cod_produs' => 'MLW065', 'sku' => ''],
        ['cod_produs' => 'MLW066', 'sku' => ''],
        ['cod_produs' => 'MLW067', 'sku' => ''],
        ['cod_produs' => 'MLW068', 'sku' => ''],
        ['cod_produs' => 'MLW069', 'sku' => ''],
        ['cod_produs' => 'MLW070', 'sku' => ''],
        ['cod_produs' => 'MLW071', 'sku' => ''],
        ['cod_produs' => 'MLW072', 'sku' => ''],
        ['cod_produs' => 'MLW073', 'sku' => ''],
        ['cod_produs' => 'MLW074', 'sku' => ''],
        ['cod_produs' => 'MLW075', 'sku' => ''],
        ['cod_produs' => 'MLW076', 'sku' => ''],
        ['cod_produs' => 'MLW077', 'sku' => ''],
        ['cod_produs' => 'MLW078', 'sku' => ''],
        ['cod_produs' => 'MLW079', 'sku' => ''],
        ['cod_produs' => 'MLW080', 'sku' => ''],
        ['cod_produs' => 'MLW081', 'sku' => ''],
        ['cod_produs' => 'MLW082', 'sku' => ''],
        ['cod_produs' => 'MLW083', 'sku' => ''],
        ['cod_produs' => 'MLW084', 'sku' => ''],
        ['cod_produs' => 'MLW085', 'sku' => ''],
        ['cod_produs' => 'MLW086', 'sku' => ''],
        ['cod_produs' => 'MLW087', 'sku' => ''],
        ['cod_produs' => 'MLW088', 'sku' => ''],
        ['cod_produs' => 'MLW089', 'sku' => ''],
        ['cod_produs' => 'MLW090', 'sku' => ''],
        ['cod_produs' => 'MLW091', 'sku' => ''],
        ['cod_produs' => 'MLW092', 'sku' => ''],
        ['cod_produs' => 'MLW093', 'sku' => ''],
        ['cod_produs' => 'MLW094', 'sku' => 'SD-009364'],
        ['cod_produs' => 'MLW095', 'sku' => 'SD-009365'],
        ['cod_produs' => 'MLW096', 'sku' => 'SD-009367'],
        ['cod_produs' => 'MLW097', 'sku' => 'SD-009368'],
        ['cod_produs' => 'MLW098', 'sku' => 'SD-009369'],
        ['cod_produs' => 'MLW099', 'sku' => 'SD-009370'],
        ['cod_produs' => 'MLW100', 'sku' => 'SD-009371'],
        ['cod_produs' => 'MLW101', 'sku' => 'SD-009372'],
        ['cod_produs' => 'MLW102', 'sku' => 'SD-009373'],
        ['cod_produs' => 'MLW103', 'sku' => 'SD-009374'],
        ['cod_produs' => 'MLW557', 'sku' => ''],
        ['cod_produs' => 'MLW146', 'sku' => ''],
        ['cod_produs' => 'MLW147', 'sku' => ''],
        ['cod_produs' => 'MLW148', 'sku' => ''],
        ['cod_produs' => 'MLW149', 'sku' => ''],
        ['cod_produs' => 'MLW150', 'sku' => ''],
        ['cod_produs' => 'MLW151', 'sku' => ''],
        ['cod_produs' => 'MLW152', 'sku' => ''],
        ['cod_produs' => 'MLW153', 'sku' => ''],
        ['cod_produs' => 'MLW154', 'sku' => ''],
        ['cod_produs' => 'MLW155', 'sku' => ''],
        ['cod_produs' => 'MLW156', 'sku' => ''],
        ['cod_produs' => 'MLW157', 'sku' => ''],
        ['cod_produs' => 'MLW158', 'sku' => ''],
        ['cod_produs' => 'MLW159', 'sku' => ''],
        ['cod_produs' => 'MLW160', 'sku' => ''],
        ['cod_produs' => 'MLW161', 'sku' => ''],
        ['cod_produs' => 'MLW162', 'sku' => ''],
        ['cod_produs' => 'MLW163', 'sku' => ''],
        ['cod_produs' => 'MLW164', 'sku' => ''],
        ['cod_produs' => 'MLW165', 'sku' => ''],
        ['cod_produs' => 'MLW166', 'sku' => ''],
        ['cod_produs' => 'MLW167', 'sku' => ''],
        ['cod_produs' => 'MLW168', 'sku' => ''],
        ['cod_produs' => 'MLW169', 'sku' => ''],
        ['cod_produs' => 'MLW170', 'sku' => ''],
        ['cod_produs' => 'MLW171', 'sku' => ''],
        ['cod_produs' => 'MLW172', 'sku' => ''],
        ['cod_produs' => 'MLW173', 'sku' => ''],
        ['cod_produs' => 'MLW174', 'sku' => ''],
        ['cod_produs' => 'MLW175', 'sku' => ''],
        ['cod_produs' => 'MLW176', 'sku' => ''],
        ['cod_produs' => 'MLW177', 'sku' => ''],
        ['cod_produs' => 'MLW178', 'sku' => ''],
        ['cod_produs' => 'MLW179', 'sku' => ''],
        ['cod_produs' => 'MLW180', 'sku' => ''],
        ['cod_produs' => 'MLW181', 'sku' => ''],
        ['cod_produs' => 'MLW182', 'sku' => ''],
        ['cod_produs' => 'MLW183', 'sku' => ''],
        ['cod_produs' => 'MLW184', 'sku' => ''],
        ['cod_produs' => 'MLW185', 'sku' => ''],
        ['cod_produs' => 'MLW186', 'sku' => ''],
        ['cod_produs' => 'MLW187', 'sku' => ''],
        ['cod_produs' => 'MLW188', 'sku' => ''],
        ['cod_produs' => 'MLW189', 'sku' => ''],
        ['cod_produs' => 'MLW190', 'sku' => ''],
        ['cod_produs' => 'MLW191', 'sku' => ''],
        ['cod_produs' => 'MLW192', 'sku' => ''],
        ['cod_produs' => 'MLW193', 'sku' => ''],
        ['cod_produs' => 'MLW194', 'sku' => ''],
        ['cod_produs' => 'MLW104', 'sku' => 'SD-009375'],
        ['cod_produs' => 'MLW105', 'sku' => 'SD-009376'],
        ['cod_produs' => 'MLW106', 'sku' => 'SD-009377'],
        ['cod_produs' => 'MLW108', 'sku' => 'SD-009379'],
        ['cod_produs' => 'MLW109', 'sku' => 'SD-009380'],
        ['cod_produs' => 'MLW110', 'sku' => 'SD-009383'],
        ['cod_produs' => 'MLW111', 'sku' => 'SD-009381'],
        ['cod_produs' => 'MLW112', 'sku' => 'SD-009382'],
        ['cod_produs' => 'MLW113', 'sku' => 'SD-009384'],
        ['cod_produs' => 'MLW114', 'sku' => 'SD-009385'],
        ['cod_produs' => 'MLW115', 'sku' => 'SD-009386'],
        ['cod_produs' => 'MLW116', 'sku' => 'SD-009387'],
        ['cod_produs' => 'MLW118', 'sku' => 'SD-009389'],
        ['cod_produs' => 'MLW119', 'sku' => 'SD-009390'],
        ['cod_produs' => 'MLW121', 'sku' => 'SD-009392'],
        ['cod_produs' => 'MLW122', 'sku' => 'SD-009393'],
        ['cod_produs' => 'MLW130', 'sku' => 'SD-009401'],
        ['cod_produs' => 'MLW131', 'sku' => 'SD-009402'],
        ['cod_produs' => 'MLW141', 'sku' => 'SD-009412'],
        ['cod_produs' => 'MLW550', 'sku' => 'SD-009864'],
        ['cod_produs' => 'MLW552', 'sku' => 'SD-009866'],
        ['cod_produs' => 'MLW145', 'sku' => 'SD-009416'],
        ['cod_produs' => 'MLW195', 'sku' => ''],
        ['cod_produs' => 'MLW196', 'sku' => ''],
        ['cod_produs' => 'MLW206', 'sku' => ''],
        ['cod_produs' => 'MLW207', 'sku' => ''],
        ['cod_produs' => 'MLW208', 'sku' => ''],
        ['cod_produs' => 'MLW209', 'sku' => ''],
        ['cod_produs' => 'MLW210', 'sku' => ''],
        ['cod_produs' => 'MLW211', 'sku' => ''],
        ['cod_produs' => 'MLW212', 'sku' => ''],
        ['cod_produs' => 'MLW216', 'sku' => ''],
        ['cod_produs' => 'MLW224', 'sku' => ''],
        ['cod_produs' => 'MLW225', 'sku' => ''],
        ['cod_produs' => 'MLW228', 'sku' => ''],
        ['cod_produs' => 'MLW229', 'sku' => ''],
        ['cod_produs' => 'MLW230', 'sku' => ''],
        ['cod_produs' => 'MLW231', 'sku' => ''],
        ['cod_produs' => 'MLW233', 'sku' => ''],
        ['cod_produs' => 'MLW234', 'sku' => ''],
        ['cod_produs' => 'MLW235', 'sku' => ''],
        ['cod_produs' => 'MLW238', 'sku' => ''],
        ['cod_produs' => 'MLW240', 'sku' => ''],
        ['cod_produs' => 'MLW241', 'sku' => ''],
        ['cod_produs' => 'MLW242', 'sku' => ''],
        ['cod_produs' => 'MLW244', 'sku' => ''],
        ['cod_produs' => 'MLW245', 'sku' => ''],
        ['cod_produs' => 'MLW246', 'sku' => ''],
        ['cod_produs' => 'MLW247', 'sku' => ''],
        ['cod_produs' => 'MLW249', 'sku' => ''],
        ['cod_produs' => 'MLW250', 'sku' => ''],
        ['cod_produs' => 'MLW251', 'sku' => ''],
        ['cod_produs' => 'MLW253', 'sku' => ''],
        ['cod_produs' => 'MLW256', 'sku' => ''],
        ['cod_produs' => 'MLW258', 'sku' => ''],
        ['cod_produs' => 'MLW260', 'sku' => ''],
        ['cod_produs' => 'MLW262', 'sku' => ''],
        ['cod_produs' => 'MLW264', 'sku' => ''],
        ['cod_produs' => 'MLW266', 'sku' => ''],
        ['cod_produs' => 'MLW267', 'sku' => ''],
        ['cod_produs' => 'MLW268', 'sku' => ''],
        ['cod_produs' => 'MLW282', 'sku' => ''],
        ['cod_produs' => 'MLW283', 'sku' => ''],
        ['cod_produs' => 'MLW284', 'sku' => ''],
        ['cod_produs' => 'MLW289', 'sku' => ''],
        ['cod_produs' => 'MLW291', 'sku' => ''],
        ['cod_produs' => 'MLW302', 'sku' => ''],
        ['cod_produs' => 'MLW303', 'sku' => ''],
        ['cod_produs' => 'MLW304', 'sku' => ''],
        ['cod_produs' => 'MLW305', 'sku' => ''],
        ['cod_produs' => 'MLW306', 'sku' => ''],
        ['cod_produs' => 'MLW307', 'sku' => ''],
        ['cod_produs' => 'MLW308', 'sku' => ''],
        ['cod_produs' => 'MLW309', 'sku' => ''],
        ['cod_produs' => 'MLW310', 'sku' => ''],
        ['cod_produs' => 'MLW311', 'sku' => ''],
        ['cod_produs' => 'MLW312', 'sku' => ''],
        ['cod_produs' => 'MLW313', 'sku' => ''],
        ['cod_produs' => 'MLW314', 'sku' => ''],
        ['cod_produs' => 'MLW315', 'sku' => ''],
        ['cod_produs' => 'MLW316', 'sku' => ''],
        ['cod_produs' => 'MLW317', 'sku' => ''],
        ['cod_produs' => 'MLW318', 'sku' => ''],
        ['cod_produs' => 'MLW319', 'sku' => ''],
        ['cod_produs' => 'MLW299', 'sku' => 'SD-009594'],
        ['cod_produs' => 'MLW330', 'sku' => ''],
        ['cod_produs' => 'MLW331', 'sku' => ''],
        ['cod_produs' => 'MLW332', 'sku' => ''],
        ['cod_produs' => 'MLW333', 'sku' => ''],
        ['cod_produs' => 'MLW334', 'sku' => ''],
        ['cod_produs' => 'MLW335', 'sku' => ''],
        ['cod_produs' => 'MLW336', 'sku' => ''],
        ['cod_produs' => 'MLW337', 'sku' => ''],
        ['cod_produs' => 'MLW338', 'sku' => ''],
        ['cod_produs' => 'MLW339', 'sku' => ''],
        ['cod_produs' => 'MLW340', 'sku' => ''],
        ['cod_produs' => 'MLW341', 'sku' => ''],
        ['cod_produs' => 'MLW342', 'sku' => ''],
        ['cod_produs' => 'MLW343', 'sku' => ''],
        ['cod_produs' => 'MLW344', 'sku' => ''],
        ['cod_produs' => 'MLW345', 'sku' => ''],
        ['cod_produs' => 'MLW346', 'sku' => ''],
        ['cod_produs' => 'MLW347', 'sku' => ''],
        ['cod_produs' => 'MLW348', 'sku' => ''],
        ['cod_produs' => 'MLW349', 'sku' => ''],
        ['cod_produs' => 'MLW350', 'sku' => ''],
        ['cod_produs' => 'MLW351', 'sku' => ''],
        ['cod_produs' => 'MLW352', 'sku' => ''],
        ['cod_produs' => 'MLW353', 'sku' => ''],
        ['cod_produs' => 'MLW354', 'sku' => ''],
        ['cod_produs' => 'MLW355', 'sku' => ''],
        ['cod_produs' => 'MLW356', 'sku' => ''],
        ['cod_produs' => 'MLW357', 'sku' => ''],
        ['cod_produs' => 'MLW358', 'sku' => ''],
        ['cod_produs' => 'MLW359', 'sku' => ''],
        ['cod_produs' => 'MLW360', 'sku' => ''],
        ['cod_produs' => 'MLW361', 'sku' => ''],
        ['cod_produs' => 'MLW362', 'sku' => ''],
        ['cod_produs' => 'MLW363', 'sku' => ''],
        ['cod_produs' => 'MLW364', 'sku' => ''],
        ['cod_produs' => 'MLW365', 'sku' => ''],
        ['cod_produs' => 'MLW366', 'sku' => ''],
        ['cod_produs' => 'MLW367', 'sku' => ''],
        ['cod_produs' => 'MLW368', 'sku' => ''],
        ['cod_produs' => 'MLW369', 'sku' => ''],
        ['cod_produs' => 'MLW370', 'sku' => ''],
        ['cod_produs' => 'MLW371', 'sku' => ''],
        ['cod_produs' => 'MLW372', 'sku' => ''],
        ['cod_produs' => 'MLW373', 'sku' => ''],
        ['cod_produs' => 'MLW374', 'sku' => ''],
        ['cod_produs' => 'MLW375', 'sku' => ''],
        ['cod_produs' => 'MLW376', 'sku' => ''],
        ['cod_produs' => 'MLW377', 'sku' => ''],
        ['cod_produs' => 'MLW378', 'sku' => ''],
        ['cod_produs' => 'MLW379', 'sku' => ''],
        ['cod_produs' => 'MLW380', 'sku' => ''],
        ['cod_produs' => 'MLW381', 'sku' => ''],
        ['cod_produs' => 'MLW382', 'sku' => ''],
        ['cod_produs' => 'MLW383', 'sku' => ''],
        ['cod_produs' => 'MLW384', 'sku' => ''],
        ['cod_produs' => 'MLW385', 'sku' => ''],
        ['cod_produs' => 'MLW386', 'sku' => ''],
        ['cod_produs' => 'MLW387', 'sku' => ''],
        ['cod_produs' => 'MLW388', 'sku' => ''],
        ['cod_produs' => 'MLW389', 'sku' => ''],
        ['cod_produs' => 'MLW390', 'sku' => ''],
        ['cod_produs' => 'MLW391', 'sku' => ''],
        ['cod_produs' => 'MLW392', 'sku' => ''],
        ['cod_produs' => 'MLW393', 'sku' => ''],
        ['cod_produs' => 'MLW394', 'sku' => ''],
        ['cod_produs' => 'MLW395', 'sku' => ''],
        ['cod_produs' => 'MLW396', 'sku' => ''],
        ['cod_produs' => 'MLW397', 'sku' => ''],
        ['cod_produs' => 'MLW398', 'sku' => ''],
        ['cod_produs' => 'MLW399', 'sku' => ''],
        ['cod_produs' => 'MLW400', 'sku' => ''],
        ['cod_produs' => 'MLW401', 'sku' => ''],
        ['cod_produs' => 'MLW402', 'sku' => ''],
        ['cod_produs' => 'MLW403', 'sku' => ''],
        ['cod_produs' => 'MLW404', 'sku' => ''],
        ['cod_produs' => 'MLW405', 'sku' => ''],
        ['cod_produs' => 'MLW406', 'sku' => ''],
        ['cod_produs' => 'MLW407', 'sku' => ''],
        ['cod_produs' => 'MLW408', 'sku' => ''],
        ['cod_produs' => 'MLW409', 'sku' => ''],
        ['cod_produs' => 'MLW410', 'sku' => ''],
        ['cod_produs' => 'MLW411', 'sku' => ''],
        ['cod_produs' => 'MLW412', 'sku' => ''],
        ['cod_produs' => 'MLW413', 'sku' => ''],
        ['cod_produs' => 'MLW414', 'sku' => ''],
        ['cod_produs' => 'MLW415', 'sku' => ''],
        ['cod_produs' => 'MLW416', 'sku' => ''],
        ['cod_produs' => 'MLW417', 'sku' => ''],
        ['cod_produs' => 'MLW682', 'sku' => ''],
        ['cod_produs' => 'MLW683', 'sku' => ''],
        ['cod_produs' => 'MLW684', 'sku' => ''],
        ['cod_produs' => 'MLW685', 'sku' => ''],
        ['cod_produs' => 'MLW686', 'sku' => ''],
        ['cod_produs' => 'MLW687', 'sku' => ''],
        ['cod_produs' => 'MLW418', 'sku' => ''],
        ['cod_produs' => 'MLW419', 'sku' => ''],
        ['cod_produs' => 'MLW420', 'sku' => ''],
        ['cod_produs' => 'MLW421', 'sku' => ''],
        ['cod_produs' => 'MLW422', 'sku' => ''],
        ['cod_produs' => 'MLW423', 'sku' => ''],
        ['cod_produs' => 'MLW424', 'sku' => ''],
        ['cod_produs' => 'MLW425', 'sku' => ''],
        ['cod_produs' => 'MLW426', 'sku' => ''],
        ['cod_produs' => 'MLW427', 'sku' => ''],
        ['cod_produs' => 'MLW428', 'sku' => ''],
        ['cod_produs' => 'MLW430', 'sku' => ''],
        ['cod_produs' => 'MLW431', 'sku' => ''],
        ['cod_produs' => 'MLW432', 'sku' => ''],
        ['cod_produs' => 'MLW433', 'sku' => ''],
        ['cod_produs' => 'MLW434', 'sku' => ''],
        ['cod_produs' => 'MLW435', 'sku' => ''],
        ['cod_produs' => 'MLW436', 'sku' => ''],
        ['cod_produs' => 'MLW437', 'sku' => ''],
        ['cod_produs' => 'MLW438', 'sku' => ''],
        ['cod_produs' => 'MLW439', 'sku' => ''],
        ['cod_produs' => 'MLW440', 'sku' => ''],
        ['cod_produs' => 'MLW441', 'sku' => ''],
        ['cod_produs' => 'MLW449', 'sku' => ''],
        ['cod_produs' => 'MLW450', 'sku' => ''],
        ['cod_produs' => 'MLW454', 'sku' => ''],
        ['cod_produs' => 'MLW455', 'sku' => ''],
        ['cod_produs' => 'MLW458', 'sku' => ''],
        ['cod_produs' => 'MLW442', 'sku' => 'SD-009740'],
        ['cod_produs' => 'MLW443', 'sku' => 'SD-009741'],
        ['cod_produs' => 'MLW444', 'sku' => 'SD-009742'],
        ['cod_produs' => 'MLW445', 'sku' => 'SD-009743'],
        ['cod_produs' => 'MLW483', 'sku' => ''],
        ['cod_produs' => 'MLW489', 'sku' => ''],
        ['cod_produs' => 'MLW446', 'sku' => 'SD-009744'],
        ['cod_produs' => 'MLW447', 'sku' => 'SD-009745'],
        ['cod_produs' => 'MLW448', 'sku' => 'SD-009746'],
        ['cod_produs' => 'MLW460', 'sku' => 'SD-009769'],
        ['cod_produs' => 'MLW461', 'sku' => 'SD-009770'],
        ['cod_produs' => 'MLW462', 'sku' => 'SD-009771'],
        ['cod_produs' => 'MLW464', 'sku' => 'SD-009773'],
        ['cod_produs' => 'MLW468', 'sku' => 'SD-009777'],
        ['cod_produs' => 'MLW469', 'sku' => 'SD-009778'],
        ['cod_produs' => 'MLW524', 'sku' => ''],
        ['cod_produs' => 'MLW525', 'sku' => ''],
        ['cod_produs' => 'MLW472', 'sku' => 'SD-009787'],
        ['cod_produs' => 'MLW473', 'sku' => 'SD-009788'],
        ['cod_produs' => 'MLW474', 'sku' => 'SD-009789'],
        ['cod_produs' => 'MLW572', 'sku' => ''],
        ['cod_produs' => 'MLW573', 'sku' => ''],
        ['cod_produs' => 'MLW574', 'sku' => ''],
        ['cod_produs' => 'MLW575', 'sku' => ''],
        ['cod_produs' => 'MLW576', 'sku' => ''],
        ['cod_produs' => 'MLW577', 'sku' => ''],
        ['cod_produs' => 'MLW578', 'sku' => ''],
        ['cod_produs' => 'MLW579', 'sku' => ''],
        ['cod_produs' => 'MLW580', 'sku' => ''],
        ['cod_produs' => 'MLW581', 'sku' => ''],
        ['cod_produs' => 'MLW585', 'sku' => ''],
        ['cod_produs' => 'MLW584', 'sku' => ''],
        ['cod_produs' => 'MLW509', 'sku' => 'SD-009845'],
        ['cod_produs' => 'MLW510', 'sku' => 'SD-009846'],
        ['cod_produs' => 'MLW511', 'sku' => 'SD-009847'],
        ['cod_produs' => 'MLW518', 'sku' => 'SD-009857'],
        ['cod_produs' => 'MLW519', 'sku' => 'SD-009858'],
        ['cod_produs' => 'MLW520', 'sku' => 'SD-009859'],
        ['cod_produs' => 'MLW521', 'sku' => 'SD-009860'],
        ['cod_produs' => 'MLW522', 'sku' => 'SD-009862'],
        ['cod_produs' => 'MLW558', 'sku' => 'SD-010286'],
        ['cod_produs' => 'MLW559', 'sku' => 'SD-010287'],
        ['cod_produs' => 'MLW564', 'sku' => 'SD-010282'],
        ['cod_produs' => 'MLW565', 'sku' => 'SD-010283'],
        ['cod_produs' => 'MLW628', 'sku' => ''],
        ['cod_produs' => 'MLW629', 'sku' => ''],
        ['cod_produs' => 'MLW630', 'sku' => ''],
        ['cod_produs' => 'MLW631', 'sku' => ''],
        ['cod_produs' => 'MLW632', 'sku' => ''],
        ['cod_produs' => 'MLW633', 'sku' => ''],
        ['cod_produs' => 'MLW634', 'sku' => ''],
        ['cod_produs' => 'MLW635', 'sku' => ''],
        ['cod_produs' => 'MLW636', 'sku' => ''],
        ['cod_produs' => 'MLW637', 'sku' => ''],
        ['cod_produs' => 'MLW638', 'sku' => ''],
        ['cod_produs' => 'MLW639', 'sku' => ''],
        ['cod_produs' => 'MLW640', 'sku' => ''],
        ['cod_produs' => 'MLW641', 'sku' => ''],
        ['cod_produs' => 'MLW567', 'sku' => ''],
        ['cod_produs' => 'MLW643', 'sku' => ''],
        ['cod_produs' => 'MLW644', 'sku' => ''],
        ['cod_produs' => 'MLW645', 'sku' => ''],
        ['cod_produs' => 'MLW646', 'sku' => ''],
        ['cod_produs' => 'MLW647', 'sku' => ''],
        ['cod_produs' => 'MLW648', 'sku' => ''],
        ['cod_produs' => 'MLW649', 'sku' => ''],
        ['cod_produs' => 'MLW650', 'sku' => ''],
        ['cod_produs' => 'MLW651', 'sku' => ''],
        ['cod_produs' => 'MLW657', 'sku' => ''],
        ['cod_produs' => 'MLW658', 'sku' => ''],
        ['cod_produs' => 'MLW659', 'sku' => ''],
        ['cod_produs' => 'MLW660', 'sku' => ''],
        ['cod_produs' => 'MLW661', 'sku' => ''],
        ['cod_produs' => 'MLW662', 'sku' => ''],
        ['cod_produs' => 'MLW663', 'sku' => ''],
        ['cod_produs' => 'MLW664', 'sku' => ''],
        ['cod_produs' => 'MLW665', 'sku' => ''],
        ['cod_produs' => 'MLW666', 'sku' => ''],
        ['cod_produs' => 'MLW667', 'sku' => ''],
        ['cod_produs' => 'MLW668', 'sku' => ''],
        ['cod_produs' => 'MLW669', 'sku' => ''],
        ['cod_produs' => 'MLW670', 'sku' => ''],
        ['cod_produs' => 'MLW671', 'sku' => ''],
        ['cod_produs' => 'MLW672', 'sku' => ''],
        ['cod_produs' => 'MLW673', 'sku' => ''],
        ['cod_produs' => 'MLW674', 'sku' => ''],
        ['cod_produs' => 'MLW675', 'sku' => ''],
        ['cod_produs' => 'MLW676', 'sku' => ''],
        ['cod_produs' => 'MLW677', 'sku' => ''],
        ['cod_produs' => 'MLW678', 'sku' => ''],
        ['cod_produs' => 'MLW679', 'sku' => ''],
        ['cod_produs' => 'MLW566', 'sku' => 'SD-010284'],
        ['cod_produs' => 'MLW569', 'sku' => 'SD-010558'],
        ['cod_produs' => 'MLW570', 'sku' => 'SD-010559'],
        ['cod_produs' => 'MLW571', 'sku' => 'SD-010560'],
        ['cod_produs' => 'MLW583', 'sku' => 'SD-010572'],
        ['cod_produs' => 'MLW586', 'sku' => 'SD-010575'],
        ['cod_produs' => 'MLW700', 'sku' => 'SD-010627'],
        ['cod_produs' => 'MLW701', 'sku' => 'SD-010629'],
        ['cod_produs' => 'BLT005', 'sku' => ''],
        ['cod_produs' => 'BLT181', 'sku' => ''],
        ['cod_produs' => 'BLT078', 'sku' => ''],
        ['cod_produs' => 'BLT075', 'sku' => ''],
        ['cod_produs' => 'BLT054', 'sku' => ''],
        ['cod_produs' => 'BLT204', 'sku' => ''],
        ['cod_produs' => 'BLT087', 'sku' => ''],
        ['cod_produs' => 'BLT069', 'sku' => ''],
        ['cod_produs' => 'BLT034', 'sku' => ''],
        ['cod_produs' => 'BLT059', 'sku' => ''],
        ['cod_produs' => 'BLT266', 'sku' => ''],
        ['cod_produs' => 'BLT120', 'sku' => ''],
        ['cod_produs' => 'BLT013', 'sku' => ''],
        ['cod_produs' => 'BLT268', 'sku' => ''],
        ['cod_produs' => 'BLT227', 'sku' => ''],
        ['cod_produs' => 'BLT143', 'sku' => ''],
        ['cod_produs' => 'BLT067', 'sku' => ''],
        ['cod_produs' => 'BLT095', 'sku' => ''],
        ['cod_produs' => 'BLT116', 'sku' => ''],
        ['cod_produs' => 'BLT030', 'sku' => ''],
        ['cod_produs' => 'BLT121', 'sku' => ''],
        ['cod_produs' => 'BLT124', 'sku' => ''],
        ['cod_produs' => 'BLT129', 'sku' => ''],
        ['cod_produs' => 'BLT185', 'sku' => ''],
        ['cod_produs' => 'BLT118', 'sku' => ''],
        ['cod_produs' => 'BLT098', 'sku' => ''],
        ['cod_produs' => 'BLT233', 'sku' => ''],
        ['cod_produs' => 'BLT020', 'sku' => ''],
        ['cod_produs' => 'BLT247', 'sku' => ''],
        ['cod_produs' => 'BLT103', 'sku' => ''],
        ['cod_produs' => 'BLT127', 'sku' => ''],
        ['cod_produs' => 'BLT157', 'sku' => ''],
        ['cod_produs' => 'BLT195', 'sku' => ''],
        ['cod_produs' => 'BLT257', 'sku' => ''],
        ['cod_produs' => 'BLT165', 'sku' => ''],
        ['cod_produs' => 'BLT123', 'sku' => ''],
        ['cod_produs' => 'BLT080', 'sku' => ''],
        ['cod_produs' => 'BLT115', 'sku' => ''],
        ['cod_produs' => 'BLT102', 'sku' => ''],
        ['cod_produs' => 'BLT137', 'sku' => ''],
        ['cod_produs' => 'BLT086', 'sku' => ''],
        ['cod_produs' => 'BLT148', 'sku' => ''],
        ['cod_produs' => 'BLT175', 'sku' => ''],
        ['cod_produs' => 'BLT135', 'sku' => ''],
        ['cod_produs' => 'BLT228', 'sku' => ''],
        ['cod_produs' => 'BLT131', 'sku' => ''],
        ['cod_produs' => 'BLT154', 'sku' => ''],
        ['cod_produs' => 'BLT139', 'sku' => ''],
        ['cod_produs' => 'BLT251', 'sku' => ''],
        ['cod_produs' => 'BLT003', 'sku' => ''],
        ['cod_produs' => 'BLT243', 'sku' => ''],
        ['cod_produs' => 'BLT144', 'sku' => ''],
        ['cod_produs' => 'BLT142', 'sku' => ''],
        ['cod_produs' => 'BLT242', 'sku' => ''],
        ['cod_produs' => 'BLT112', 'sku' => ''],
        ['cod_produs' => 'BLT138', 'sku' => ''],
        ['cod_produs' => 'BLT214', 'sku' => ''],
        ['cod_produs' => 'BLT130', 'sku' => ''],
        ['cod_produs' => 'BLT079', 'sku' => ''],
        ['cod_produs' => 'BLT141', 'sku' => ''],
        ['cod_produs' => 'BLT053', 'sku' => ''],
        ['cod_produs' => 'BLT021', 'sku' => ''],
        ['cod_produs' => 'BLT114', 'sku' => ''],
        ['cod_produs' => 'BLT082', 'sku' => ''],
        ['cod_produs' => 'BLT011', 'sku' => ''],
        ['cod_produs' => 'BLT156', 'sku' => ''],
        ['cod_produs' => 'BLT161', 'sku' => ''],
        ['cod_produs' => 'BLT027', 'sku' => ''],
        ['cod_produs' => 'BLT105', 'sku' => ''],
        ['cod_produs' => 'BLT109', 'sku' => ''],
        ['cod_produs' => 'BLT088', 'sku' => ''],
        ['cod_produs' => 'BLT041', 'sku' => ''],
        ['cod_produs' => 'BLT180', 'sku' => ''],
        ['cod_produs' => 'BLT189', 'sku' => ''],
        ['cod_produs' => 'BLT155', 'sku' => ''],
        ['cod_produs' => 'BLT191', 'sku' => ''],
        ['cod_produs' => 'BLT147', 'sku' => ''],
        ['cod_produs' => 'BLT056', 'sku' => ''],
        ['cod_produs' => 'BLT077', 'sku' => ''],
        ['cod_produs' => 'BLT073', 'sku' => ''],
        ['cod_produs' => 'BLT019', 'sku' => ''],
        ['cod_produs' => 'BLT028', 'sku' => ''],
        ['cod_produs' => 'BLT216', 'sku' => ''],
        ['cod_produs' => 'BLT169', 'sku' => ''],
        ['cod_produs' => 'BLT167', 'sku' => ''],
        ['cod_produs' => 'BLT058', 'sku' => ''],
        ['cod_produs' => 'BLT176', 'sku' => ''],
        ['cod_produs' => 'BLT174', 'sku' => ''],
        ['cod_produs' => 'BLT081', 'sku' => ''],
        ['cod_produs' => 'BLT076', 'sku' => ''],
        ['cod_produs' => 'BLT068', 'sku' => ''],
        ['cod_produs' => 'BLT250', 'sku' => ''],
        ['cod_produs' => 'BLT170', 'sku' => ''],
        ['cod_produs' => 'BLT162', 'sku' => ''],
        ['cod_produs' => 'BLT215', 'sku' => ''],
        ['cod_produs' => 'BLT258', 'sku' => ''],
        ['cod_produs' => 'BLT153', 'sku' => ''],
        ['cod_produs' => 'BLT219', 'sku' => ''],
        ['cod_produs' => 'BLT061', 'sku' => ''],
        ['cod_produs' => 'BLT163', 'sku' => ''],
        ['cod_produs' => 'BLT090', 'sku' => ''],
        ['cod_produs' => 'BLT172', 'sku' => ''],
        ['cod_produs' => 'BLT171', 'sku' => ''],
        ['cod_produs' => 'BLT166', 'sku' => ''],
        ['cod_produs' => 'BLT208', 'sku' => ''],
        ['cod_produs' => 'BLT245', 'sku' => ''],
        ['cod_produs' => 'BLT241', 'sku' => ''],
        ['cod_produs' => 'BLT196', 'sku' => ''],
        ['cod_produs' => 'BLT188', 'sku' => ''],
        ['cod_produs' => 'BLT149', 'sku' => ''],
        ['cod_produs' => 'BLT012', 'sku' => ''],
        ['cod_produs' => 'BLT133', 'sku' => ''],
        ['cod_produs' => 'BLT173', 'sku' => ''],
        ['cod_produs' => 'BLT197', 'sku' => ''],
        ['cod_produs' => 'BLT094', 'sku' => ''],
        ['cod_produs' => 'BLT146', 'sku' => ''],
        ['cod_produs' => 'BLT046', 'sku' => ''],
        ['cod_produs' => 'BLT070', 'sku' => ''],
        ['cod_produs' => 'BLT062', 'sku' => ''],
        ['cod_produs' => 'BLT164', 'sku' => ''],
        ['cod_produs' => 'BLT125', 'sku' => ''],
        ['cod_produs' => 'BLT206', 'sku' => ''],
        ['cod_produs' => 'BLT183', 'sku' => ''],
        ['cod_produs' => 'BLT039', 'sku' => ''],
        ['cod_produs' => 'BLT179', 'sku' => ''],
        ['cod_produs' => 'BLT049', 'sku' => ''],
        ['cod_produs' => 'BLT047', 'sku' => ''],
        ['cod_produs' => 'BLT066', 'sku' => ''],
        ['cod_produs' => 'BLT064', 'sku' => ''],
        ['cod_produs' => 'BLT187', 'sku' => ''],
        ['cod_produs' => 'BLT110', 'sku' => ''],
        ['cod_produs' => 'BLT151', 'sku' => ''],
        ['cod_produs' => 'BLT184', 'sku' => ''],
        ['cod_produs' => 'BLT225', 'sku' => ''],
        ['cod_produs' => 'BLT203', 'sku' => ''],
        ['cod_produs' => 'BLT008', 'sku' => ''],
        ['cod_produs' => 'BLT222', 'sku' => ''],
        ['cod_produs' => 'BLT158', 'sku' => ''],
        ['cod_produs' => 'BLT006', 'sku' => ''],
        ['cod_produs' => 'BLT192', 'sku' => ''],
        ['cod_produs' => 'BLT096', 'sku' => ''],
        ['cod_produs' => 'BLT190', 'sku' => ''],
        ['cod_produs' => 'BLT033', 'sku' => ''],
        ['cod_produs' => 'BLT235', 'sku' => ''],
        ['cod_produs' => 'BLT237', 'sku' => ''],
        ['cod_produs' => 'BLT186', 'sku' => ''],
        ['cod_produs' => 'BLT259', 'sku' => ''],
        ['cod_produs' => 'BLT211', 'sku' => ''],
        ['cod_produs' => 'BLT220', 'sku' => ''],
        ['cod_produs' => 'BLT152', 'sku' => ''],
        ['cod_produs' => 'BLT230', 'sku' => ''],
        ['cod_produs' => 'BLT265', 'sku' => ''],
        ['cod_produs' => 'BLT182', 'sku' => ''],
        ['cod_produs' => 'BLT246', 'sku' => ''],
        ['cod_produs' => 'BLT178', 'sku' => ''],
        ['cod_produs' => 'BLT261', 'sku' => ''],
        ['cod_produs' => 'BLT200', 'sku' => ''],
        ['cod_produs' => 'BLT244', 'sku' => ''],
        ['cod_produs' => 'BLT134', 'sku' => ''],
        ['cod_produs' => 'BLT249', 'sku' => ''],
        ['cod_produs' => 'BLT043', 'sku' => ''],
        ['cod_produs' => 'BLT213', 'sku' => ''],
        ['cod_produs' => 'BLT198', 'sku' => ''],
        ['cod_produs' => 'BLT009', 'sku' => ''],
        ['cod_produs' => 'BLT194', 'sku' => ''],
        ['cod_produs' => 'BLT042', 'sku' => ''],
        ['cod_produs' => 'BLT117', 'sku' => ''],
        ['cod_produs' => 'BLT199', 'sku' => ''],
        ['cod_produs' => 'BLT202', 'sku' => ''],
        ['cod_produs' => 'BLT023', 'sku' => ''],
        ['cod_produs' => 'BLT168', 'sku' => ''],
        ['cod_produs' => 'BLT207', 'sku' => ''],
        ['cod_produs' => 'BLT045', 'sku' => ''],
        ['cod_produs' => 'BLT160', 'sku' => ''],
        ['cod_produs' => 'BLT253', 'sku' => ''],
        ['cod_produs' => 'BLT140', 'sku' => ''],
        ['cod_produs' => 'BLT136', 'sku' => ''],
        ['cod_produs' => 'BLT097', 'sku' => ''],
        ['cod_produs' => 'BLT201', 'sku' => ''],
        ['cod_produs' => 'BLT060', 'sku' => ''],
        ['cod_produs' => 'BLT254', 'sku' => ''],
        ['cod_produs' => 'BLT252', 'sku' => ''],
        ['cod_produs' => 'BLT119', 'sku' => ''],
        ['cod_produs' => 'BLT255', 'sku' => ''],
        ['cod_produs' => 'BLT239', 'sku' => ''],
        ['cod_produs' => 'BLT063', 'sku' => ''],
        ['cod_produs' => 'BLT051', 'sku' => ''],
        ['cod_produs' => 'BLT236', 'sku' => ''],
        ['cod_produs' => 'BLT232', 'sku' => ''],
        ['cod_produs' => 'BLT224', 'sku' => ''],
        ['cod_produs' => 'BLT240', 'sku' => ''],
        ['cod_produs' => 'BLT262', 'sku' => ''],
        ['cod_produs' => 'BLT260', 'sku' => ''],
        ['cod_produs' => 'BLT256', 'sku' => ''],
        ['cod_produs' => 'BLT035', 'sku' => ''],
        ['cod_produs' => 'BLT223', 'sku' => ''],
        ['cod_produs' => 'BLT126', 'sku' => ''],
        ['cod_produs' => 'BLT092', 'sku' => ''],
        ['cod_produs' => 'BLT193', 'sku' => ''],
        ['cod_produs' => 'BLT177', 'sku' => ''],
        ['cod_produs' => 'BLT212', 'sku' => ''],
        ['cod_produs' => 'BLT145', 'sku' => ''],
        ['cod_produs' => 'BLT065', 'sku' => ''],
        ['cod_produs' => 'BLT007', 'sku' => ''],
        ['cod_produs' => 'BLT055', 'sku' => ''],
        ['cod_produs' => 'BLT210', 'sku' => ''],
        ['cod_produs' => 'BLT093', 'sku' => ''],
        ['cod_produs' => 'BLT040', 'sku' => ''],
        ['cod_produs' => 'BLT099', 'sku' => ''],
        ['cod_produs' => 'BLT015', 'sku' => ''],
        ['cod_produs' => 'BLT085', 'sku' => ''],
        ['cod_produs' => 'BLT218', 'sku' => ''],
        ['cod_produs' => 'BLT226', 'sku' => ''],
        ['cod_produs' => 'BLT248', 'sku' => ''],
        ['cod_produs' => 'BLT101', 'sku' => ''],
        ['cod_produs' => 'BLT083', 'sku' => ''],
        ['cod_produs' => 'BLT091', 'sku' => ''],
        ['cod_produs' => 'BLT057', 'sku' => ''],
        ['cod_produs' => 'BLT128', 'sku' => ''],
        ['cod_produs' => 'BLT074', 'sku' => ''],
        ['cod_produs' => 'BLT104', 'sku' => ''],
        ['cod_produs' => 'BLT106', 'sku' => ''],
        ['cod_produs' => 'BLT084', 'sku' => ''],
        ['cod_produs' => 'BLT111', 'sku' => ''],
        ['cod_produs' => 'BLT004', 'sku' => ''],
        ['cod_produs' => 'BLT089', 'sku' => ''],
        ['cod_produs' => 'BLT122', 'sku' => ''],
        ['cod_produs' => 'BLT132', 'sku' => ''],
        ['cod_produs' => 'BLT050', 'sku' => ''],
        ['cod_produs' => 'BLT071', 'sku' => ''],
        ['cod_produs' => 'BLT267', 'sku' => ''],
        ['cod_produs' => 'BLT263', 'sku' => ''],
        ['cod_produs' => 'BLT044', 'sku' => ''],
        ['cod_produs' => 'BLT264', 'sku' => ''],
        ['cod_produs' => 'BLT048', 'sku' => ''],
        ['cod_produs' => 'BLT269', 'sku' => ''],
        ['cod_produs' => 'BLT113', 'sku' => ''],
        ['cod_produs' => 'BLT221', 'sku' => ''],
        ['cod_produs' => 'BLT108', 'sku' => ''],
        ['cod_produs' => 'BLT217', 'sku' => ''],
        ['cod_produs' => 'BLT100', 'sku' => ''],
        ['cod_produs' => 'BLT209', 'sku' => ''],
        ['cod_produs' => 'BLT231', 'sku' => ''],
        ['cod_produs' => 'BLT072', 'sku' => ''],
        ['cod_produs' => 'MLW652', 'sku' => 'SD-010452'],
        ['cod_produs' => 'MLW653', 'sku' => 'SD-010453'],
        ['cod_produs' => 'MLW654', 'sku' => 'SD-010454'],
        ['cod_produs' => 'MLW582', 'sku' => 'SD-010571'],
        ['cod_produs' => 'MLW680', 'sku' => 'SD-010608'],
        ['cod_produs' => 'MLW681', 'sku' => 'SD-010609'],
        ['cod_produs' => 'MLW694', 'sku' => 'SD-010622'],
        ['cod_produs' => 'MLW695', 'sku' => 'SD-010623'],
        ['cod_produs' => 'MLW703', 'sku' => 'SD-010762'],
        ['cod_produs' => 'MLW704', 'sku' => 'SD-010927'],
        ['cod_produs' => 'MLW691', 'sku' => 'SD-010619'],
        ['cod_produs' => 'BLT277', 'sku' => ''],
        ['cod_produs' => 'BLT276', 'sku' => ''],
        ['cod_produs' => 'BLT272', 'sku' => ''],
        ['cod_produs' => 'BLT275', 'sku' => ''],
        ['cod_produs' => 'BLT271', 'sku' => ''],
        ['cod_produs' => 'BLT274', 'sku' => ''],
        ['cod_produs' => 'BLT270', 'sku' => ''],
        ['cod_produs' => 'BLT273', 'sku' => ''],
        ['cod_produs' => 'BLT016', 'sku' => ''],
        ['cod_produs' => 'BLT010', 'sku' => ''],
        ['cod_produs' => 'BLT002', 'sku' => ''],
        ['cod_produs' => 'BLT014', 'sku' => ''],
        ['cod_produs' => 'BLT024', 'sku' => ''],
        ['cod_produs' => 'BLT029', 'sku' => ''],
        ['cod_produs' => 'BLT031', 'sku' => ''],
        ['cod_produs' => 'BLT026', 'sku' => ''],
        ['cod_produs' => 'BLT017', 'sku' => ''],
        ['cod_produs' => 'BLT025', 'sku' => ''],
        ['cod_produs' => 'MLW709', 'sku' => 'SD-011886'],
        ['cod_produs' => 'MLW710', 'sku' => 'SD-011905'],
        ['cod_produs' => 'MLW711', 'sku' => 'SD-011906'],
        ['cod_produs' => 'MLW723', 'sku' => ''],
        ['cod_produs' => 'MLW725', 'sku' => ''],
        ['cod_produs' => 'MLW726', 'sku' => ''],
        ['cod_produs' => 'MLW727', 'sku' => ''],
        ['cod_produs' => 'MLW713', 'sku' => 'SD-011908'],
        ['cod_produs' => 'MLW714', 'sku' => 'SD-011909'],
        ['cod_produs' => 'MLW716', 'sku' => 'SD-011920'],
        ['cod_produs' => 'MLW717', 'sku' => 'SD-011921'],
        ['cod_produs' => 'MLW718', 'sku' => 'SD-011922'],
        ['cod_produs' => 'BLT281', 'sku' => 'SD-012032'],
        ['cod_produs' => 'MLW751', 'sku' => ''],
        ['cod_produs' => 'BLT282', 'sku' => 'SD-012033'],
        ['cod_produs' => 'BLT283', 'sku' => 'SD-012034'],
        ['cod_produs' => 'BLT284', 'sku' => 'SD-012035'],
        ['cod_produs' => 'BLT285', 'sku' => 'SD-012036'],
        ['cod_produs' => 'BLT286', 'sku' => 'SD-012037'],
        ['cod_produs' => 'BLT287', 'sku' => 'SD-012038'],
        ['cod_produs' => 'BLT288', 'sku' => 'SD-012039'],
        ['cod_produs' => 'BLT289', 'sku' => 'SD-012040'],
        ['cod_produs' => 'BLT290', 'sku' => 'SD-012041'],
        ['cod_produs' => 'BLT291', 'sku' => 'SD-012042'],
        ['cod_produs' => 'BLT292', 'sku' => 'SD-012043'],
        ['cod_produs' => 'MLW606', 'sku' => 'SD-012369'],
        ['cod_produs' => 'MLW705', 'sku' => 'SD-011056'],
        ['cod_produs' => 'MLW560', 'sku' => 'SD-012368'],
        ['cod_produs' => 'MLW721', 'sku' => 'SD-012051'],
        ['cod_produs' => 'MLW708', 'sku' => 'SD-011300'],
        ['cod_produs' => 'MLW706', 'sku' => 'SD-011298'],
        ['cod_produs' => 'MLW707', 'sku' => 'SD-011299'],
        ['cod_produs' => 'MLW722', 'sku' => 'SD-012052'],
        ['cod_produs' => 'MLW735', 'sku' => 'SD-013330'],
        ['cod_produs' => 'MLW736', 'sku' => 'SD-013480'],
        ['cod_produs' => 'STI193', 'sku' => ''],
        ['cod_produs' => 'SKL032', 'sku' => ''],
        ['cod_produs' => 'FAB028', 'sku' => 'SD-018931'],
        ['cod_produs' => 'FAB086', 'sku' => 'SD-019139'],
        ['cod_produs' => 'FAB087', 'sku' => 'SD-019140'],
        ['cod_produs' => 'FAB098', 'sku' => 'SD-019151'],
        ['cod_produs' => 'FAB103', 'sku' => 'SD-019156'],
        ['cod_produs' => 'FAB101', 'sku' => 'SD-019154'],
        ['cod_produs' => 'FAB070', 'sku' => 'SD-019123'],
        ['cod_produs' => 'FAB067', 'sku' => 'SD-019120'],
        ['cod_produs' => 'STI511', 'sku' => 'SD-019493'],
        ['cod_produs' => 'STI508', 'sku' => 'SD-019477'],
        ['cod_produs' => 'CER049', 'sku' => 'SD-019496'],
        ['cod_produs' => 'CAI303', 'sku' => ''],
        ['cod_produs' => 'CAI308', 'sku' => ''],
        ['cod_produs' => 'CAI311', 'sku' => ''],
        ['cod_produs' => 'CAI312', 'sku' => ''],
        ['cod_produs' => 'CAI313', 'sku' => ''],
        ['cod_produs' => 'CAI301', 'sku' => 'SD-019270'],
        ['cod_produs' => 'CAI302', 'sku' => 'SD-019271'],
        ['cod_produs' => 'CAI304', 'sku' => 'SD-019272'],
        ['cod_produs' => 'CAI305', 'sku' => 'SD-019275'],
        ['cod_produs' => 'CAI306', 'sku' => 'SD-019276'],
        ['cod_produs' => 'CAI309', 'sku' => 'SD-019282'],
        ['cod_produs' => 'CAI310', 'sku' => 'SD-019283'],
        ['cod_produs' => 'CAI057', 'sku' => ''],
        ['cod_produs' => 'SKG294', 'sku' => 'SD-019506'],
        ['cod_produs' => 'SKG296', 'sku' => 'SD-019508'],
        ['cod_produs' => 'SKG297', 'sku' => 'SD-019509'],
        ['cod_produs' => 'SKG301', 'sku' => 'SD-019513'],
        ['cod_produs' => 'MLW741', 'sku' => 'SD-015186'],
        ['cod_produs' => 'MLW767', 'sku' => 'SD-017124'],
        ['cod_produs' => 'MLW780', 'sku' => 'SD-017123'],
        ['cod_produs' => 'SKP308', 'sku' => 'SD-019614'],
        ['cod_produs' => 'PMK082', 'sku' => 'SD-019616'],
        ['cod_produs' => 'SKE090', 'sku' => ''],
        ['cod_produs' => 'SKE096', 'sku' => ''],
        ['cod_produs' => 'SKE101', 'sku' => ''],
        ['cod_produs' => 'SKE099', 'sku' => ''],
        ['cod_produs' => 'SKG311', 'sku' => 'SD-019691'],
        ['cod_produs' => 'DCA092', 'sku' => ''],
        ['cod_produs' => 'DCA094', 'sku' => ''],
        ['cod_produs' => 'RIG656', 'sku' => 'SD-019927'],
        ['cod_produs' => 'RIG661', 'sku' => 'SD-019899'],
        ['cod_produs' => 'JOC135', 'sku' => 'SD-020536'],
        ['cod_produs' => 'JOC137', 'sku' => 'SD-020538'],
        ['cod_produs' => 'JOC147', 'sku' => 'SD-020545'],
        ['cod_produs' => 'JOC148', 'sku' => 'SD-020528'],
        ['cod_produs' => 'JOC141', 'sku' => 'SD-020530'],
        ['cod_produs' => 'JOC140', 'sku' => 'SD-020541'],
        ['cod_produs' => 'JOC139', 'sku' => 'SD-020540'],
        ['cod_produs' => 'JOC150', 'sku' => 'SD-020532'],
        ['cod_produs' => 'JOC149', 'sku' => 'SD-020529'],
        ['cod_produs' => 'JOC152', 'sku' => 'SD-020547'],
        ['cod_produs' => 'JOC154', 'sku' => 'SD-020550'],
        ['cod_produs' => 'JOC153', 'sku' => 'SD-020551'],
        ['cod_produs' => 'JOC145', 'sku' => 'SD-020543'],
        ['cod_produs' => 'JOC146', 'sku' => 'SD-020544'],
        ['cod_produs' => 'JOC143', 'sku' => 'SD-020531'],
        ['cod_produs' => 'JOC142', 'sku' => 'SD-020542'],
        ['cod_produs' => 'PRO058', 'sku' => 'SD-020249'],
        ['cod_produs' => 'LEMCTYC65WN', 'sku' => 'SD-020403'],
        ['cod_produs' => 'LEMCTYCTCA', 'sku' => 'SD-020409'],
        ['cod_produs' => 'LEMCSTCF60V', 'sku' => 'SD-020411'],
        ['cod_produs' => 'LEMCPSTCF60R', 'sku' => 'SD-020413'],
        ['cod_produs' => 'LEMCSTCF602MN', 'sku' => 'SD-020414'],
        ['cod_produs' => 'LEMCSTCF602MV', 'sku' => 'SD-020415'],
        ['cod_produs' => 'LEMCFCTC100N', 'sku' => 'SD-020418'],
        ['cod_produs' => 'LEMCULH2A', 'sku' => 'SD-020420'],
        ['cod_produs' => 'LEMCBAUATC5N', 'sku' => 'SD-020425'],
        ['cod_produs' => 'JOC157', 'sku' => 'SD-020614'],
        ['cod_produs' => 'JOC158', 'sku' => 'SD-020615'],
        ['cod_produs' => 'JOC159', 'sku' => 'SD-020616'],
        ['cod_produs' => 'JOC160', 'sku' => 'SD-020617'],
        ['cod_produs' => 'JOC161', 'sku' => 'SD-020618'],
        ['cod_produs' => 'JOC162', 'sku' => 'SD-020619'],
        ['cod_produs' => 'JOC163', 'sku' => 'SD-020621'],
        ['cod_produs' => 'JOC164', 'sku' => 'SD-020622'],
        ['cod_produs' => 'ROD417', 'sku' => ''],
        ['cod_produs' => 'CAI374', 'sku' => 'SD-020657'],
        ['cod_produs' => 'CAI368', 'sku' => 'SD-020651'],
        ['cod_produs' => 'CAI363', 'sku' => 'SD-020646'],
        ['cod_produs' => 'CAI362', 'sku' => 'SD-020645'],
        ['cod_produs' => 'CAI361', 'sku' => 'SD-020644'],
        ['cod_produs' => 'CAI360', 'sku' => 'SD-020643'],
        ['cod_produs' => 'CAI359', 'sku' => 'SD-020642'],
        ['cod_produs' => 'CAI356', 'sku' => 'SD-020639'],
        ['cod_produs' => 'CAI348', 'sku' => 'SD-020631'],
        ['cod_produs' => 'CAI346', 'sku' => 'SD-020629'],
        ['cod_produs' => 'CAI344', 'sku' => 'SD-020620'],
        ['cod_produs' => 'MLW812', 'sku' => ''],
        ['cod_produs' => 'MLW806', 'sku' => ''],
        ['cod_produs' => 'MLW810', 'sku' => ''],
        ['cod_produs' => 'MLW804', 'sku' => ''],
        ['cod_produs' => 'MLW807', 'sku' => ''],
        ['cod_produs' => 'MLW811', 'sku' => ''],
        ['cod_produs' => 'MLW808', 'sku' => ''],
        ['cod_produs' => 'MLW803', 'sku' => ''],
        ['cod_produs' => 'MLW809', 'sku' => ''],
        ['cod_produs' => 'MLW802', 'sku' => ''],
        ['cod_produs' => 'MLW805', 'sku' => ''],
        ['cod_produs' => 'MLW824', 'sku' => ''],
        ['cod_produs' => 'MLW817', 'sku' => ''],
        ['cod_produs' => 'MLW822', 'sku' => ''],
        ['cod_produs' => 'MLW816', 'sku' => ''],
        ['cod_produs' => 'MLW818', 'sku' => ''],
        ['cod_produs' => 'MLW823', 'sku' => ''],
        ['cod_produs' => 'MLW819', 'sku' => ''],
        ['cod_produs' => 'MLW815', 'sku' => ''],
        ['cod_produs' => 'MLW820', 'sku' => ''],
        ['cod_produs' => 'MLW821', 'sku' => ''],
        ['cod_produs' => 'MLW814', 'sku' => ''],
        ['cod_produs' => 'MLW813', 'sku' => ''],
        ['cod_produs' => 'VES556', 'sku' => 'SD-020786'],
        ['cod_produs' => 'VES557', 'sku' => 'SD-020787'],
        ['cod_produs' => 'VES558', 'sku' => 'SD-020788'],
        ['cod_produs' => 'VES559', 'sku' => 'SD-020789'],
        ['cod_produs' => 'VES560', 'sku' => 'SD-020790'],
        ['cod_produs' => 'STI503', 'sku' => ''],
        ['cod_produs' => 'STI504', 'sku' => ''],
        ['cod_produs' => 'JOC166', 'sku' => 'SD-020909'],
        ['cod_produs' => 'JOC167', 'sku' => 'SD-020910'],
        ['cod_produs' => 'JOC168', 'sku' => 'SD-020911'],
        ['cod_produs' => 'PGE121', 'sku' => 'SD-020914'],
        ['cod_produs' => 'SKG332', 'sku' => ''],
        ['cod_produs' => 'AMB066', 'sku' => ''],
        ['cod_produs' => 'AMB065', 'sku' => ''],
        ['cod_produs' => 'SKR604', 'sku' => 'SD-021210'],
        ['cod_produs' => 'AMB064', 'sku' => ''],
        ['cod_produs' => 'AMB063', 'sku' => ''],
        ['cod_produs' => 'AMB1057', 'sku' => 'SD-021225'],
        ['cod_produs' => 'VES579', 'sku' => 'SD-021226'],
        ['cod_produs' => 'AMB067', 'sku' => 'SD-021043'],
        ['cod_produs' => 'AMB062', 'sku' => 'SD-021034'],
        ['cod_produs' => 'CAI376', 'sku' => 'SD-020772'],
        ['cod_produs' => 'CAI378', 'sku' => 'SD-020770'],
        ['cod_produs' => 'SKR619', 'sku' => 'SD-021296'],
        ['cod_produs' => 'CAI390', 'sku' => 'SD-021227'],
        ['cod_produs' => 'CAI391', 'sku' => 'SD-021228'],
    ];
}

function papetarie_storefront_register_aperta_sync_page(): void
{
    add_menu_page(
        __('Sincronizare Aperta', 'papetarie-storefront'),
        __('Sincronizare Aperta', 'papetarie-storefront'),
        'manage_woocommerce',
        'papetarie-aperta-sync',
        'papetarie_storefront_render_aperta_sync_page',
        'dashicons-update',
        58
    );
}
add_action('admin_menu', 'papetarie_storefront_register_aperta_sync_page');

function papetarie_storefront_aperta_format_relative(?int $timestamp): string
{
    if (!$timestamp) {
        return __('niciodată', 'papetarie-storefront');
    }

    $diff = time() - $timestamp;
    $when = date_i18n('d.m.Y H:i', $timestamp);

    if ($diff < 0) {
        return sprintf(
            /* translators: %s: formatted date */
            __('programat pentru %s', 'papetarie-storefront'),
            $when
        );
    }

    return sprintf(
        /* translators: 1: human time diff, 2: formatted date */
        __('acum %1$s (%2$s)', 'papetarie-storefront'),
        human_time_diff($timestamp, time()),
        $when
    );
}

/**
 * Ca format_relative(), dar pentru o acțiune programată care încă nu a
 * rulat: dacă data e deja trecută, acțiunea e "restantă" (WP-Cron nu a
 * declanșat-o încă), nu "s-a întâmplat acum X ore".
 */
function papetarie_storefront_aperta_format_next_run(?int $timestamp): string
{
    if (!$timestamp) {
        return __('niciodată', 'papetarie-storefront');
    }

    $when = date_i18n('d.m.Y H:i', $timestamp);

    if ($timestamp > time()) {
        return sprintf(
            /* translators: %s: formatted date */
            __('programat pentru %s', 'papetarie-storefront'),
            $when
        );
    }

    $overdueBy = time() - $timestamp;

    // Restanta de cateva minute e normala (jitter), dar dupa un sfert de ora
    // e semn clar ca WP-Cron nu se declanseaza singur - spunem direct ce sa faca.
    if ($overdueBy > 15 * MINUTE_IN_SECONDS) {
        return sprintf(
            /* translators: 1: human time diff, 2: formatted date */
            __('restantă de %1$s (era programată la %2$s) — cronul automat nu s-a declanșat încă; apasă „Rulează acum” mai jos', 'papetarie-storefront'),
            human_time_diff($timestamp, time()),
            $when
        );
    }

    return sprintf(
        /* translators: %s: formatted date */
        __('pornește în curând (era programată la %s)', 'papetarie-storefront'),
        $when
    );
}

/**
 * Toate acțiunile "pending" pentru un hook, indiferent de argumente (relevant
 * pentru stoc, care are 10 sloturi/zi cu argumente diferite ['hour' => N]).
 *
 * @return array{count: int, next: ?int}
 */
function papetarie_storefront_aperta_pending_summary(string $hook): array
{
    if (!class_exists('ActionScheduler_Store')) {
        return ['count' => 0, 'next' => null];
    }

    $store = ActionScheduler_Store::instance();
    $ids = $store->query_actions([
        'hook' => $hook,
        'group' => 'aperta-sync',
        'status' => ActionScheduler_Store::STATUS_PENDING,
        'per_page' => 20,
        'orderby' => 'date',
        'order' => 'ASC',
    ]);

    if (empty($ids)) {
        return ['count' => 0, 'next' => null];
    }

    $action = $store->fetch_action((int) $ids[0]);
    $date = $action->get_schedule()->get_date();

    return [
        'count' => count($ids),
        'next' => $date ? $date->getTimestamp() : null,
    ];
}

function papetarie_storefront_render_aperta_sync_page(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('Nu ai permisiunea necesară pentru această pagină.', 'papetarie-storefront'));
    }

    $hasActionScheduler = class_exists('ActionScheduler_Store') && class_exists('ActionScheduler_Logger');

    $lastFullSync = (int) get_option('pap_aperta_last_full_sync', 0) ?: null;
    $lastStockSync = (int) get_option('pap_aperta_last_stock_sync', 0) ?: null;
    $productsSchedule = papetarie_storefront_aperta_pending_summary('pap_aperta_sync_products_start');
    $stockSchedule = papetarie_storefront_aperta_pending_summary('pap_aperta_sync_stock_start');

    global $wpdb;
    // Doar produsele-parinte (nu si variatiile individuale de culoare/marime,
    // care sunt post-uri separate cu propriul lor SKU) - numarul pe care il
    // recunoaste un administrator ca "un produs".
    $productCount = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = '_pap_aperta_cod_produs' AND p.post_type = 'product'"
    );
    $skuCount = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_pap_aperta_sku'"
    );
    // Produse din importul vechi (JSON static, fara SKU) - nu se pot reconcilia
    // fiabil cu feed-ul Aperta si de-asta e recomandat sa fie curatate inainte
    // de primul sync real, ca sa nu ramana duplicate pe site.
    $legacyCount = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = '_pap_import_key' AND p.post_type = 'product'
         AND NOT EXISTS (
             SELECT 1 FROM {$wpdb->postmeta} pm2
             WHERE pm2.post_id = p.ID AND pm2.meta_key IN ('_pap_aperta_cod_produs', '_pap_aperta_sku')
         )"
    );

    // Istoric permanent al rulărilor COMPLETE (nu bucăți individuale) - scris
    // de papetarie_storefront_aperta_record_history(), independent de
    // Action Scheduler (care își șterge singur acțiunile vechi după o vreme).
    $history = get_option('pap_aperta_sync_history', []);
    $history = is_array($history) ? array_reverse($history) : [];
    $history = array_slice($history, 0, 30);
    $retainedRunLogIds = get_option('pap_aperta_run_log_ids', []);
    $retainedRunLogIds = is_array($retainedRunLogIds) ? array_flip($retainedRunLogIds) : [];
    $productsProgress = papetarie_storefront_aperta_progress_get('products');
    $stockProgress = papetarie_storefront_aperta_progress_get('stock');
    ?>
    <div class="wrap pap-aperta-wrap">
      <h1><?php esc_html_e('Sincronizare Aperta', 'papetarie-storefront'); ?></h1>
      <p><?php esc_html_e('Rezumat simplu al sincronizării automate cu feed-urile Aperta (produse zilnic, stoc orar). Nu ține totul separat — e doar o vedere filtrată peste Action Scheduler.', 'papetarie-storefront'); ?></p>

      <?php if (!$hasActionScheduler) : ?>
        <div class="notice notice-error"><p><?php esc_html_e('Action Scheduler (parte din WooCommerce) nu pare disponibil.', 'papetarie-storefront'); ?></p></div>
      <?php endif; ?>

      <div id="pap-aperta-message" class="notice inline" hidden><p></p></div>

      <div class="pap-aperta-cards">
        <div class="pap-aperta-card">
          <span class="pap-aperta-card-label"><?php esc_html_e('Ultima sincronizare completă de produse', 'papetarie-storefront'); ?></span>
          <strong><?php echo esc_html(papetarie_storefront_aperta_format_relative($lastFullSync)); ?></strong>
        </div>
        <div class="pap-aperta-card">
          <span class="pap-aperta-card-label"><?php esc_html_e('Ultima sincronizare de stoc', 'papetarie-storefront'); ?></span>
          <strong><?php echo esc_html(papetarie_storefront_aperta_format_relative($lastStockSync)); ?></strong>
        </div>
        <div class="pap-aperta-card">
          <span class="pap-aperta-card-label"><?php esc_html_e('Produse din Aperta pe site', 'papetarie-storefront'); ?></span>
          <strong><?php echo esc_html((string) $productCount); ?></strong>
          <span class="pap-aperta-card-note"><?php echo esc_html(sprintf(
              /* translators: %d: total SKU/variant count */
              __('%d variante individuale în total — dacă un produs are, de ex., 5 culori diferite, fiecare culoare are propriul cod (SKU) și e numărată separat aici', 'papetarie-storefront'),
              $skuCount
          )); ?></span>
        </div>
      </div>

      <?php if ($legacyCount > 0) : ?>
        <div class="notice notice-warning inline pap-aperta-legacy-notice">
          <p>
            <?php echo esc_html(sprintf(
                /* translators: %d: number of legacy products found */
                _n(
                    'Am găsit %d produs din importul vechi (fără SKU, nu poate fi reconciliat automat cu Aperta).',
                    'Am găsit %d produse din importul vechi (fără SKU, nu pot fi reconciliate automat cu Aperta).',
                    $legacyCount,
                    'papetarie-storefront'
                ),
                $legacyCount
            )); ?>
            <?php esc_html_e('Recomandat: mută-le în coșul de gunoi înainte de prima sincronizare Aperta, ca să nu rămână duplicate pe site.', 'papetarie-storefront'); ?>
          </p>
          <p>
            <button type="button" class="button button-secondary" id="pap-aperta-purge-legacy"><?php esc_html_e('Curăță produsele vechi', 'papetarie-storefront'); ?></button>
          </p>
        </div>
      <?php endif; ?>

      <h2><?php esc_html_e('Program de sincronizare', 'papetarie-storefront'); ?></h2>
      <table class="widefat striped pap-aperta-table">
        <thead>
          <tr>
            <th><?php esc_html_e('Flux', 'papetarie-storefront'); ?></th>
            <th><?php esc_html_e('Frecvență', 'papetarie-storefront'); ?></th>
            <th><?php esc_html_e('Durată estimată', 'papetarie-storefront'); ?></th>
            <th><?php esc_html_e('Afectează site-ul live?', 'papetarie-storefront'); ?></th>
            <th><?php esc_html_e('Următoarea rulare', 'papetarie-storefront'); ?></th>
            <th><?php esc_html_e('Acțiune', 'papetarie-storefront'); ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <strong><?php esc_html_e('Produse (catalog complet)', 'papetarie-storefront'); ?></strong>
              <details>
                <summary><?php esc_html_e('ce face', 'papetarie-storefront'); ?></summary>
                <ul class="pap-aperta-flow-facts">
                  <li><?php esc_html_e('Descarcă feed.csv (tot catalogul: nume, descriere, preț, categorie, brand, poze) și actualizează fiecare produs, cu prețul recalculat cu discountul din contract.', 'papetarie-storefront'); ?></li>
                  <li><?php esc_html_e('Poze: NU redescarcă pozele deja existente — verifică după link-ul sursă și descarcă doar ce e nou sau schimbat.', 'papetarie-storefront'); ?></li>
                  <li><?php esc_html_e('Stoc: nu este atins de acest job (asta face fluxul de Stoc, separat).', 'papetarie-storefront'); ?></li>
                </ul>
              </details>
            </td>
            <td><?php esc_html_e('1x/zi, ~3:10', 'papetarie-storefront'); ?></td>
            <td><?php esc_html_e('Variabil — mai lent la primele rulări (~3–5 ore), apoi mult mai rapid odată ce majoritatea produselor rămân neschimbate de la o noapte la alta (sar peste procesare dacă nu s-a schimbat nimic)', 'papetarie-storefront'); ?></td>
            <td><?php esc_html_e('Nu — site-ul rămâne funcțional tot timpul, produsele se actualizează treptat, unul câte unul, iar rularea e programată noaptea, în afara orelor cu trafic.', 'papetarie-storefront'); ?></td>
            <td><?php echo esc_html(papetarie_storefront_aperta_format_next_run($productsSchedule['next'])); ?></td>
            <td><button type="button" class="button button-primary" data-pap-run="products"><?php esc_html_e('Rulează acum', 'papetarie-storefront'); ?></button></td>
          </tr>
          <tr>
            <td>
              <strong><?php esc_html_e('Stoc', 'papetarie-storefront'); ?></strong>
              <details>
                <summary><?php esc_html_e('ce face', 'papetarie-storefront'); ?></summary>
                <ul class="pap-aperta-flow-facts">
                  <li><?php esc_html_e('Descarcă feed-stoc.csv (câte un cod/SKU pentru fiecare variantă de produs — ex. fiecare culoare are codul ei — și cantitatea aferentă) și actualizează STRICT cantitatea de stoc și starea „în stoc / fără stoc”.', 'papetarie-storefront'); ?></li>
                  <li><?php esc_html_e('Nu atinge preț, descriere, categorie sau poze — doar cantitatea.', 'papetarie-storefront'); ?></li>
                  <li><?php esc_html_e('De ce de 10x/zi: copiază exact orele la care Aperta își actualizează propriul stoc.', 'papetarie-storefront'); ?></li>
                </ul>
              </details>
            </td>
            <td><?php echo esc_html(sprintf(
                /* translators: %d: number of daily runs */
                __('%d x/zi (1:30 și din oră în oră 9:30–17:30)', 'papetarie-storefront'),
                $stockSchedule['count'] ?: 10
            )); ?></td>
            <td><?php esc_html_e('Variabil — mai lent prima dată, apoi rapid (sare peste stocurile neschimbate)', 'papetarie-storefront'); ?></td>
            <td><?php esc_html_e('Nu — actualizare rapidă, fără impact vizibil.', 'papetarie-storefront'); ?></td>
            <td><?php echo esc_html(papetarie_storefront_aperta_format_next_run($stockSchedule['next'])); ?></td>
            <td><button type="button" class="button button-primary" data-pap-run="stock"><?php esc_html_e('Rulează acum', 'papetarie-storefront'); ?></button></td>
          </tr>
        </tbody>
      </table>
      <p class="description"><?php esc_html_e('„Rulează acum” pornește sincronizarea imediat, fără să aștepte programul (util pentru testare).', 'papetarie-storefront'); ?></p>

      <div class="pap-aperta-card" style="margin: 16px 0 24px;">
        <span class="pap-aperta-card-label"><?php esc_html_e('Actualizare rapidă filtre', 'papetarie-storefront'); ?></span>
        <p class="description" style="margin: 4px 0 10px;"><?php esc_html_e('Aplică pe produsele deja sincronizate cele mai noi reguli de extragere a filtrelor (culoare, format, material etc.), fără să refacă toată sincronizarea (fără poze, fără prețuri) — durează câteva secunde, nu ore. Folosește asta după ce urci o versiune nouă de cod cu reguli de filtre schimbate.', 'papetarie-storefront'); ?></p>
        <button type="button" class="button button-secondary" id="pap-aperta-backfill-attrs"><?php esc_html_e('Actualizează filtrele acum', 'papetarie-storefront'); ?></button>
        <p class="description" id="pap-aperta-backfill-status" style="margin-top: 8px;"></p>
      </div>

      <div class="pap-aperta-card" style="margin: 16px 0 24px;">
        <span class="pap-aperta-card-label"><?php esc_html_e('Ordinea meniului', 'papetarie-storefront'); ?></span>
        <p class="description" style="margin: 4px 0 10px;"><?php esc_html_e('Repară ordinea subcategoriilor din mega-meniu (coloanele grupate logic) și curăță categoriile create greșit de sincronizare dintr-o cale de feed coruptă. Rulează instant. Folosește asta după ce sincronizarea a creat subcategorii noi sau ordinea din meniu nu se potrivește cu local.', 'papetarie-storefront'); ?></p>
        <button type="button" class="button button-secondary" id="pap-aperta-fix-menu-order"><?php esc_html_e('Repară ordinea meniului', 'papetarie-storefront'); ?></button>
        <p class="description" id="pap-aperta-fix-menu-order-status" style="margin-top: 8px;"></p>
      </div>

      <div class="pap-aperta-card" style="margin: 16px 0 24px; border-left: 4px solid #d63638;">
        <span class="pap-aperta-card-label"><?php esc_html_e('Curățenie unică — doar lista Lavinia', 'papetarie-storefront'); ?></span>
        <p class="description" style="margin: 4px 0 10px;"><?php esc_html_e('Mută în coșul de gunoi cele 1457 de produse care NU se regăsesc în lista curatată manual (Excel Lavinia, 2238 rânduri) — rămân publicate doar cele 1873 de produse potrivite. Acțiune reversibilă (coș de gunoi, nu ștergere definitivă). Rulează o singură dată.', 'papetarie-storefront'); ?></p>
        <button type="button" class="button button-secondary" id="pap-aperta-trash-extra"><?php esc_html_e('Șterge produsele din afara listei', 'papetarie-storefront'); ?></button>
        <p class="description" id="pap-aperta-trash-extra-status" style="margin-top: 8px;"></p>
      </div>

      <h2><?php esc_html_e('Progres live', 'papetarie-storefront'); ?></h2>
      <div class="pap-aperta-progress-grid">
        <?php foreach (['products' => ['label' => __('Produse', 'papetarie-storefront'), 'data' => $productsProgress], 'stock' => ['label' => __('Stoc', 'papetarie-storefront'), 'data' => $stockProgress]] as $flow => $info) : ?>
          <div class="pap-aperta-progress-card" data-pap-progress="<?php echo esc_attr($flow); ?>">
            <div class="pap-aperta-progress-head">
              <strong><?php echo esc_html($info['label']); ?></strong>
              <span data-field="status-label">—</span>
            </div>
            <div class="pap-aperta-progress-bar">
              <div class="pap-aperta-progress-bar-fill" data-field="bar" style="width:0%"></div>
            </div>
            <p class="pap-aperta-progress-meta" data-field="meta">&nbsp;</p>
            <p class="pap-aperta-progress-summary" data-field="summary" hidden></p>
            <ul class="pap-aperta-progress-log" data-field="log"></ul>
          </div>
        <?php endforeach; ?>
      </div>

      <h2><?php esc_html_e('Istoric sincronizări', 'papetarie-storefront'); ?></h2>
      <p class="description"><?php esc_html_e('O rulare completă per rând (nu bucăți individuale) - rămâne aici chiar și după ce Action Scheduler își șterge singur acțiunile vechi.', 'papetarie-storefront'); ?></p>
      <?php if (empty($history)) : ?>
        <p><?php esc_html_e('Nicio sincronizare nu s-a finalizat încă.', 'papetarie-storefront'); ?></p>
      <?php else : ?>
        <table class="widefat striped pap-aperta-table">
          <thead>
            <tr>
              <th><?php esc_html_e('Data', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Flux', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Pornit', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Verificate', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Găsite pe site', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Schimbate', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Neschimbate', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Durată', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Detalii', 'papetarie-storefront'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $row) :
                $runId = $row['run_id'] ?? '';
                $hasLog = $runId !== '' && isset($retainedRunLogIds[$runId]);
            ?>
              <tr>
                <td><?php echo esc_html(date_i18n('d.m.Y H:i', $row['finished_at'])); ?></td>
                <td><?php echo esc_html($row['flow'] === 'stock' ? __('Stoc', 'papetarie-storefront') : __('Produse', 'papetarie-storefront')); ?></td>
                <td><?php echo esc_html(($row['trigger'] ?? 'auto') === 'manual' ? __('Manual („Rulează acum”)', 'papetarie-storefront') : __('Automat (program)', 'papetarie-storefront')); ?></td>
                <td><?php echo esc_html((string) $row['total']); ?></td>
                <td><?php echo esc_html((string) $row['matched']); ?></td>
                <td><?php echo esc_html((string) $row['changed']); ?></td>
                <td><?php echo esc_html((string) $row['unchanged']); ?></td>
                <td><?php echo $row['duration'] !== null ? esc_html(floor($row['duration'] / 60) . 'm ' . ($row['duration'] % 60) . 's') : '—'; ?></td>
                <td>
                  <?php if ($hasLog) : ?>
                    <button type="button" class="button button-small" data-pap-view-log="<?php echo esc_attr($runId); ?>"><?php esc_html_e('Vezi loguri', 'papetarie-storefront'); ?></button>
                  <?php else : ?>
                    <span class="description">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php if ($hasLog) : ?>
                <tr class="pap-aperta-log-row" data-run-log-row="<?php echo esc_attr($runId); ?>" hidden>
                  <td colspan="9">
                    <ul class="pap-aperta-progress-log" data-run-log-content></ul>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p class="description"><?php esc_html_e('Log-ul detaliat (SKU + produs + ce s-a schimbat) e păstrat doar pentru ultimele 20 de rulări, ca să nu îngreuiem baza de date — rulările mai vechi rămân în tabel doar cu rezumatul.', 'papetarie-storefront'); ?></p>
      <?php endif; ?>
    </div>

    <style>
      .pap-aperta-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
        margin: 20px 0;
      }

      .pap-aperta-card {
        background: #fff;
        border: 1px solid #dcdcde;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
        padding: 16px 18px;
        display: grid;
        gap: 6px;
      }

      .pap-aperta-card-label {
        color: #646970;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .02em;
      }

      .pap-aperta-card strong {
        font-size: 15px;
        color: #1d2327;
      }

      .pap-aperta-card-note {
        color: #8c8f94;
        font-size: 11px;
      }

      .pap-aperta-flow-facts {
        margin: 8px 0 0;
        padding-left: 18px;
        color: #50575e;
      }

      .pap-aperta-flow-facts li {
        margin-bottom: 6px;
      }

      .pap-aperta-table details summary {
        cursor: pointer;
        color: #2271b1;
        font-size: 12px;
      }

      .pap-aperta-table {
        margin-top: 12px;
      }

      .pap-aperta-progress-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 14px;
        margin: 16px 0 24px;
      }

      .pap-aperta-progress-card {
        background: #fff;
        border: 1px solid #dcdcde;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
        padding: 16px 18px;
        min-width: 0;
      }

      @media (max-width: 782px) {
        .pap-aperta-progress-grid {
          grid-template-columns: 1fr;
        }
      }

      .pap-aperta-progress-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
      }

      .pap-aperta-progress-bar {
        background: #f0f0f1;
        border-radius: 999px;
        height: 10px;
        overflow: hidden;
      }

      .pap-aperta-progress-bar-fill {
        background: #2271b1;
        height: 100%;
        width: 0;
        transition: width .4s ease;
      }

      .pap-aperta-progress-card[data-status="complete"] .pap-aperta-progress-bar-fill {
        background: #1a7a35;
      }

      .pap-aperta-progress-meta {
        margin: 8px 0 0;
        color: #50575e;
        font-size: 12px;
      }

      .pap-aperta-progress-summary {
        margin: 10px 0 0;
        padding: 8px 10px;
        background: #edfaef;
        border: 1px solid #d5eedb;
        border-radius: 3px;
        color: #1a7a35;
        font-size: 12px;
        font-weight: 600;
      }

      .pap-aperta-info-wrap {
        position: relative;
        display: inline-block;
      }

      .pap-aperta-info-icon {
        display: inline-block;
        cursor: pointer;
        font-weight: 700;
        color: #1a7a35;
        background: none;
        border: none;
        border-bottom: 1px dotted currentColor;
        padding: 0;
        font-size: 12px;
        line-height: 1;
      }

      .pap-aperta-info-box {
        position: absolute;
        z-index: 10;
        top: 100%;
        right: 0;
        margin-top: 6px;
        width: 260px;
        max-width: min(260px, 85vw);
        background: #1d2327;
        color: #fff;
        font-weight: 400;
        padding: 10px 12px;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
        white-space: pre-line;
        line-height: 1.5;
      }

      @media (max-width: 480px) {
        .pap-aperta-info-box {
          right: auto;
          left: 50%;
          transform: translateX(-50%);
        }
      }

      .pap-aperta-progress-log {
        margin: 10px 0 0;
        padding: 0;
        list-style: none;
        max-height: 480px;
        overflow-y: auto;
        border-top: 1px solid #f0f0f1;
      }

      .pap-aperta-progress-log:empty {
        border-top: none;
      }

      .pap-aperta-progress-log li {
        padding: 5px 0;
        border-bottom: 1px solid #f6f7f7;
        font-size: 12px;
        color: #1d2327;
        overflow-wrap: break-word;
        word-break: break-word;
      }

      .pap-aperta-progress-log li span {
        color: #8c8f94;
        margin-right: 6px;
      }

      .pap-aperta-log-row td {
        background: #f6f7f7;
        padding: 12px 16px 16px;
      }

      .pap-aperta-log-row .pap-aperta-progress-log {
        border-top: none;
        max-height: 360px;
      }
    </style>

    <script>
      jQuery(function ($) {
        var $message = $('#pap-aperta-message');
        var nonce = '<?php echo esc_js(wp_create_nonce('pap-aperta-run-now')); ?>';
        var statusLabels = {
          idle: '<?php echo esc_js(__('Inactiv', 'papetarie-storefront')); ?>',
          starting: '<?php echo esc_js(__('Pornește…', 'papetarie-storefront')); ?>',
          running: '<?php echo esc_js(__('Rulează…', 'papetarie-storefront')); ?>',
          complete: '<?php echo esc_js(__('Finalizat', 'papetarie-storefront')); ?>'
        };
        var pollTimer = null;
        var siteProductCount = <?php echo (int) $productCount; ?>;
        var batchSizes = { products: 10, stock: 100 };
        var unitLabels = {
          products: '<?php echo esc_js(__('produse (din feed.csv)', 'papetarie-storefront')); ?>',
          stock: '<?php echo esc_js(__('variante/SKU-uri verificate (din feed-stoc.csv)', 'papetarie-storefront')); ?>'
        };

        function showMessage(type, text) {
          $message.removeClass('notice-success notice-error').addClass(type === 'success' ? 'notice-success' : 'notice-error');
          $message.find('p').text(text);
          $message.prop('hidden', false);
        }

        function timeAgo(startedAt) {
          if (!startedAt) {
            return '';
          }
          var seconds = Math.max(0, Math.floor(Date.now() / 1000) - startedAt);
          if (seconds < 60) {
            return seconds + 's';
          }
          var minutes = Math.floor(seconds / 60);
          var restSeconds = seconds % 60;
          return minutes + 'm ' + restSeconds + 's';
        }

        function renderProgress(flow, data) {
          var $card = $('[data-pap-progress="' + flow + '"]');
          if (!$card.length || !data) {
            return;
          }

          $card.attr('data-status', data.status);

          var percent = data.total > 0 ? Math.min(100, Math.round((data.processed / data.total) * 100)) : 0;
          $card.find('[data-field="bar"]').css('width', percent + '%');
          $card.find('[data-field="status-label"]').text(statusLabels[data.status] || data.status);

          var meta = '';
          if (data.status === 'idle') {
            meta = '<?php echo esc_js(__('Nicio rulare încă.', 'papetarie-storefront')); ?>';
          } else if (data.status === 'starting') {
            meta = '<?php echo esc_js(__('Se descarcă feed-ul…', 'papetarie-storefront')); ?>';
          } else if (data.status === 'complete') {
            var duration = (data.finished_at && data.started_at) ? (data.finished_at - data.started_at) : null;
            meta = data.processed + ' / ' + data.total + ' ' + (unitLabels[flow] || '');
            if (duration !== null) {
              meta += ' — <?php echo esc_js(__('finalizat în', 'papetarie-storefront')); ?> ' + Math.floor(duration / 60) + 'm ' + (duration % 60) + 's';
            }
          } else {
            meta = data.processed + ' / ' + data.total + ' ' + (unitLabels[flow] || '') + ' (' + percent + '%) — <?php echo esc_js(__('pornit acum', 'papetarie-storefront')); ?> ' + timeAgo(data.started_at)
              + ' — <?php echo esc_js(__('procesează în calupuri de', 'papetarie-storefront')); ?> ' + (batchSizes[flow] || '?') + ' <?php echo esc_js(__('simultan, o dată la ~5 secunde', 'papetarie-storefront')); ?>';
          }
          $card.find('[data-field="meta"]').text(meta);

          var $summary = $card.find('[data-field="summary"]');
          if (data.status === 'complete') {
            var summary;
            var tooltipText = '';
            if (flow === 'stock') {
              summary = '<?php echo esc_js(__('Am verificat', 'papetarie-storefront')); ?> ' + data.total + ' <?php echo esc_js(__('variante/SKU-uri (', 'papetarie-storefront')); ?>' + siteProductCount + '<?php echo esc_js(__(' produse) — actualizate:', 'papetarie-storefront')); ?> ' + data.changed
                + ', <?php echo esc_js(__('neschimbate:', 'papetarie-storefront')); ?> ' + data.unchanged + '.';
              tooltipText = '<?php echo esc_js(__('Din cele verificate,', 'papetarie-storefront')); ?> ' + data.matched + ' <?php echo esc_js(__('au fost găsite pe site.', 'papetarie-storefront')); ?>\n\n'
                + '<?php echo esc_js(__('Dacă un produs are mai multe culori sau mărimi, se numește produs variabil: e un singur produs pe site, dar fiecare culoare/mărime are propriul cod (SKU) și stoc — verificate separat.', 'papetarie-storefront')); ?>';
            } else {
              summary = '<?php echo esc_js(__('Am verificat', 'papetarie-storefront')); ?> ' + data.total + ' <?php echo esc_js(__('produse din feed.csv — create/actualizate cu modificări:', 'papetarie-storefront')); ?> ' + data.changed
                + ', <?php echo esc_js(__('neschimbate:', 'papetarie-storefront')); ?> ' + data.unchanged + '.';
              tooltipText = '<?php echo esc_js(__('„Actualizate cu modificări” înseamnă: fie e produs nou, fie prețul s-a schimbat.', 'papetarie-storefront')); ?>\n\n'
                + '<?php echo esc_js(__('Pentru un produs cu variante (culori/mărimi), e suficient ca o singură variantă să aibă preț nou ca tot produsul să conteze „schimbat”.', 'papetarie-storefront')); ?>';
            }
            $summary.empty().text(summary + ' ').append(
              $('<span class="pap-aperta-info-wrap">').append(
                $('<button type="button" class="pap-aperta-info-icon">ⓘ</button>'),
                $('<span class="pap-aperta-info-box" hidden>').text(tooltipText)
              )
            );
            $summary.prop('hidden', false);
          } else {
            $summary.prop('hidden', true);
          }

          var $log = $card.find('[data-field="log"]');
          $log.empty();
          var recent = (data.recent || []).slice().reverse();
          recent.forEach(function (item) {
            $log.append($('<li>').append($('<span>').text(item.sku)).append(document.createTextNode(item.name)));
          });
        }

        function isActive(data) {
          return data && (data.status === 'starting' || data.status === 'running');
        }

        function poll() {
          $.post(ajaxurl, {
            action: 'pap_aperta_get_progress',
            nonce: nonce
          }).done(function (response) {
            if (!response || !response.success) {
              return;
            }
            renderProgress('products', response.data.products);
            renderProgress('stock', response.data.stock);

            if (isActive(response.data.products) || isActive(response.data.stock)) {
              pollTimer = setTimeout(poll, 2500);
            } else {
              pollTimer = null;
            }
          });
        }

        function ensurePolling() {
          if (!pollTimer) {
            poll();
          }
        }

        $(document).on('click', '.pap-aperta-info-icon', function (event) {
          event.preventDefault();
          event.stopPropagation();
          var $box = $(this).siblings('.pap-aperta-info-box');
          var wasHidden = $box.prop('hidden');
          $('.pap-aperta-info-box').prop('hidden', true);
          $box.prop('hidden', !wasHidden);
        });

        $(document).on('click', function (event) {
          if (!$(event.target).closest('.pap-aperta-info-wrap').length) {
            $('.pap-aperta-info-box').prop('hidden', true);
          }
        });

        $(document).on('click', '[data-pap-view-log]', function () {
          var $button = $(this);
          var runId = $button.data('pap-view-log');
          var $row = $('[data-run-log-row="' + runId + '"]');
          var $content = $row.find('[data-run-log-content]');

          if (!$row.prop('hidden')) {
            $row.prop('hidden', true);
            $button.text('<?php echo esc_js(__('Vezi loguri', 'papetarie-storefront')); ?>');
            return;
          }

          $row.prop('hidden', false);
          $button.text('<?php echo esc_js(__('Ascunde loguri', 'papetarie-storefront')); ?>');

          if ($content.data('loaded')) {
            return;
          }

          $content.empty().append($('<li>').text('<?php echo esc_js(__('Se încarcă…', 'papetarie-storefront')); ?>'));

          $.post(ajaxurl, {
            action: 'pap_aperta_get_run_log',
            nonce: nonce,
            run_id: runId
          }).done(function (response) {
            $content.empty();
            var items = (response && response.success && response.data.items) ? response.data.items : [];
            if (!items.length) {
              $content.append($('<li>').text('<?php echo esc_js(__('Niciun detaliu salvat pentru această rulare.', 'papetarie-storefront')); ?>'));
              return;
            }
            items.forEach(function (item) {
              $content.append($('<li>').append($('<span>').text(item.sku)).append(document.createTextNode(item.name)));
            });
            $content.data('loaded', true);
          }).fail(function () {
            $content.empty().append($('<li>').text('<?php echo esc_js(__('Eroare la încărcare.', 'papetarie-storefront')); ?>'));
          });
        });

        function resetProgressCard(flow) {
          var $card = $('[data-pap-progress="' + flow + '"]');
          $card.attr('data-status', 'starting');
          $card.find('[data-field="bar"]').css('width', '0%');
          $card.find('[data-field="status-label"]').text(statusLabels.starting);
          $card.find('[data-field="meta"]').text('<?php echo esc_js(__('Se descarcă feed-ul…', 'papetarie-storefront')); ?>');
          $card.find('[data-field="summary"]').prop('hidden', true).empty();
          $card.find('[data-field="log"]').empty();
        }

        $('[data-pap-run]').on('click', function () {
          var $button = $(this);
          var flow = $button.data('pap-run');

          $button.prop('disabled', true);
          $message.prop('hidden', true);
          // Curatam imediat cardul (fara sa asteptam raspunsul serverului),
          // ca lista veche de SKU-uri sa nu ramana pe ecran in timp ce noua
          // rulare porneste - serverul oricum reseteaza "recent" la [] chiar
          // acum, dar UI-ul altfel ar arata lista veche pana la primul poll.
          resetProgressCard(flow);

          $.post(ajaxurl, {
            action: 'pap_aperta_run_now',
            nonce: nonce,
            flow: flow
          }).done(function (response) {
            if (response && response.success) {
              showMessage('success', response.data.message);
              ensurePolling();
              return;
            }
            showMessage('error', (response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('A apărut o eroare.', 'papetarie-storefront')); ?>');
          }).fail(function () {
            showMessage('error', '<?php echo esc_js(__('A apărut o eroare de conexiune.', 'papetarie-storefront')); ?>');
          }).always(function () {
            $button.prop('disabled', false);
          });
        });

        $('#pap-aperta-purge-legacy').on('click', function () {
          var $button = $(this);
          if (!window.confirm('<?php echo esc_js(__('Sigur muți produsele vechi (fără SKU) în coșul de gunoi? Cele deja migrate prin Aperta nu sunt atinse.', 'papetarie-storefront')); ?>')) {
            return;
          }

          $button.prop('disabled', true);
          $message.prop('hidden', true);

          $.post(ajaxurl, {
            action: 'pap_aperta_purge_legacy',
            nonce: nonce
          }).done(function (response) {
            if (response && response.success) {
              showMessage('success', response.data.message);
              setTimeout(function () { window.location.reload(); }, 1500);
              return;
            }
            showMessage('error', (response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('A apărut o eroare.', 'papetarie-storefront')); ?>');
            $button.prop('disabled', false);
          }).fail(function () {
            showMessage('error', '<?php echo esc_js(__('A apărut o eroare de conexiune.', 'papetarie-storefront')); ?>');
            $button.prop('disabled', false);
          });
        });

        $('#pap-aperta-backfill-attrs').on('click', function () {
          var $button = $(this);
          var $status = $('#pap-aperta-backfill-status');

          $button.prop('disabled', true);
          $status.text('<?php echo esc_js(__('Pornit…', 'papetarie-storefront')); ?>');

          function processChunk(offset) {
            $.post(ajaxurl, {
              action: 'pap_aperta_backfill_attrs',
              nonce: nonce,
              offset: offset
            }).done(function (response) {
              if (!response || !response.success) {
                $status.text((response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('A apărut o eroare.', 'papetarie-storefront')); ?>');
                $button.prop('disabled', false);
                return;
              }

              var data = response.data;
              var nextOffset = offset + data.processed;
              $status.text(nextOffset + ' / ' + data.total + ' <?php echo esc_js(__('produse verificate…', 'papetarie-storefront')); ?>');

              if (nextOffset < data.total && data.processed > 0) {
                processChunk(nextOffset);
              } else {
                $status.text('<?php echo esc_js(__('Gata —', 'papetarie-storefront')); ?> ' + data.total + ' <?php echo esc_js(__('produse verificate.', 'papetarie-storefront')); ?>');
                $button.prop('disabled', false);
              }
            }).fail(function () {
              $status.text('<?php echo esc_js(__('A apărut o eroare de conexiune.', 'papetarie-storefront')); ?>');
              $button.prop('disabled', false);
            });
          }

          processChunk(0);
        });

        $('#pap-aperta-fix-menu-order').on('click', function () {
          var $button = $(this);
          var $status = $('#pap-aperta-fix-menu-order-status');

          $button.prop('disabled', true);
          $status.text('<?php echo esc_js(__('Se repară…', 'papetarie-storefront')); ?>');

          $.post(ajaxurl, {
            action: 'pap_aperta_fix_menu_order',
            nonce: nonce
          }).done(function (response) {
            if (response && response.success) {
              $status.text(response.data.message);
            } else {
              $status.text((response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('A apărut o eroare.', 'papetarie-storefront')); ?>');
            }
            $button.prop('disabled', false);
          }).fail(function () {
            $status.text('<?php echo esc_js(__('A apărut o eroare de conexiune.', 'papetarie-storefront')); ?>');
            $button.prop('disabled', false);
          });
        });

        $('#pap-aperta-trash-extra').on('click', function () {
          var $button = $(this);
          var $status = $('#pap-aperta-trash-extra-status');

          if (!window.confirm('<?php echo esc_js(__('Sigur muți în coșul de gunoi produsele care nu sunt în lista Lavinia? Acțiune reversibilă, dar afectează site-ul live.', 'papetarie-storefront')); ?>')) {
            return;
          }

          $button.prop('disabled', true);
          $status.text('<?php echo esc_js(__('Se încarcă lista…', 'papetarie-storefront')); ?>');

          $.post(ajaxurl, {
            action: 'pap_aperta_get_cleanup_list',
            nonce: nonce
          }).done(function (listResponse) {
            if (!listResponse || !listResponse.success) {
              $status.text('<?php echo esc_js(__('Nu am putut încărca lista.', 'papetarie-storefront')); ?>');
              $button.prop('disabled', false);
              return;
            }

            var allItems = listResponse.data.items;
            var chunkSize = 150;
            var totalTrashed = 0, totalNotFound = 0, totalAlready = 0;

            function processChunk(offset) {
              if (offset >= allItems.length) {
                $status.text(
                  '<?php echo esc_js(__('Gata —', 'papetarie-storefront')); ?> ' + totalTrashed +
                  ' <?php echo esc_js(__('mutate în coș,', 'papetarie-storefront')); ?> ' + totalAlready +
                  ' <?php echo esc_js(__('deja în coș,', 'papetarie-storefront')); ?> ' + totalNotFound +
                  ' <?php echo esc_js(__('negăsite.', 'papetarie-storefront')); ?>'
                );
                $button.prop('disabled', false);
                return;
              }

              var chunk = allItems.slice(offset, offset + chunkSize);
              $status.text((offset + chunk.length) + ' / ' + allItems.length + ' <?php echo esc_js(__('procesate…', 'papetarie-storefront')); ?>');

              $.post(ajaxurl, {
                action: 'pap_aperta_trash_by_code',
                nonce: nonce,
                items: JSON.stringify(chunk)
              }).done(function (response) {
                if (!response || !response.success) {
                  $status.text((response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('A apărut o eroare.', 'papetarie-storefront')); ?>');
                  $button.prop('disabled', false);
                  return;
                }

                totalTrashed += response.data.trashed;
                totalNotFound += response.data.not_found;
                totalAlready += response.data.already_trashed;
                processChunk(offset + chunkSize);
              }).fail(function () {
                $status.text('<?php echo esc_js(__('A apărut o eroare de conexiune.', 'papetarie-storefront')); ?>');
                $button.prop('disabled', false);
              });
            }

            processChunk(0);
          }).fail(function () {
            $status.text('<?php echo esc_js(__('A apărut o eroare de conexiune.', 'papetarie-storefront')); ?>');
            $button.prop('disabled', false);
          });
        });

        // O verificare imediată la deschiderea paginii, ca să reflecte o
        // rulare deja în curs (pornită din altă filă sau automat).
        poll();
      });
    </script>
    <?php
}

function papetarie_storefront_aperta_ajax_run_now(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    $flow = isset($_POST['flow']) ? sanitize_key(wp_unslash($_POST['flow'])) : '';

    if (!in_array($flow, ['products', 'stock'], true) || !function_exists('as_enqueue_async_action')) {
        wp_send_json_error(['message' => __('Flux necunoscut.', 'papetarie-storefront')], 400);
    }

    if (!papetarie_storefront_aperta_progress_mark_starting($flow)) {
        wp_send_json_error(['message' => __('Rulează deja o sincronizare de acest tip — așteaptă să termine (vezi progresul mai jos).', 'papetarie-storefront')], 409);
    }

    $hook = $flow === 'products' ? 'pap_aperta_sync_products_start' : 'pap_aperta_sync_stock_start';
    as_enqueue_async_action($hook, ['trigger' => 'manual'], 'aperta-sync');

    wp_send_json_success(['message' => __('Pornit — vezi progresul mai jos.', 'papetarie-storefront')]);
}
add_action('wp_ajax_pap_aperta_run_now', 'papetarie_storefront_aperta_ajax_run_now');

function papetarie_storefront_aperta_ajax_get_progress(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    // In acest mediu local, WP-Cron nu se declanseaza singur (vezi nota din
    // pagina) - fiecare verificare de progres "impinge" manual coada Action
    // Scheduler mai departe, ca sincronizarea sa avanseze real cat timp
    // pagina e deschisa. Pe serverul real (cu WP-Cron functional), acest
    // apel e redundant/inofensiv - Action Scheduler isi gestioneaza singur
    // procesarea, iar aici doar o "grabeste" un pic.
    if (class_exists('ActionScheduler_QueueRunner')) {
        ActionScheduler_QueueRunner::instance()->run();
    }

    wp_send_json_success([
        'products' => papetarie_storefront_aperta_progress_get('products'),
        'stock' => papetarie_storefront_aperta_progress_get('stock'),
    ]);
}
add_action('wp_ajax_pap_aperta_get_progress', 'papetarie_storefront_aperta_ajax_get_progress');

function papetarie_storefront_aperta_ajax_get_run_log(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    $runId = isset($_POST['run_id']) ? sanitize_text_field(wp_unslash($_POST['run_id'])) : '';

    if ($runId === '') {
        wp_send_json_error(['message' => __('Rulare necunoscută.', 'papetarie-storefront')], 400);
    }

    wp_send_json_success(['items' => papetarie_storefront_aperta_get_run_log($runId)]);
}
add_action('wp_ajax_pap_aperta_get_run_log', 'papetarie_storefront_aperta_ajax_get_run_log');

/**
 * Muta in cosul de gunoi produsele importate din vechiul JSON static
 * (_pap_import_key, fara SKU) - varianta din admin a tools/purge-legacy-import-products.php,
 * declansabila cu un click, fara acces la baza de date sau linie de comanda.
 *
 * Notă: pe unele instalări wp_delete_post($id, false) s-a comportat ca ștergere
 * definitivă în loc de coș de gunoi (observat local) - dacă asta se întâmplă,
 * comportamentul WordPress-ului de bază e responsabil, nu acest cod.
 */
function papetarie_storefront_aperta_ajax_purge_legacy(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    if (!function_exists('wc_get_product')) {
        wp_send_json_error(['message' => __('WooCommerce nu pare încărcat.', 'papetarie-storefront')], 400);
    }

    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => 'any',
        'meta_key' => '_pap_import_key',
        'fields' => 'ids',
        'posts_per_page' => -1,
    ]);

    $trashed = 0;
    foreach ($ids as $id) {
        if (get_post_meta($id, '_pap_aperta_cod_produs', true) || get_post_meta($id, '_pap_aperta_sku', true)) {
            continue;
        }

        wp_delete_post($id, false);
        $trashed++;
    }

    wp_send_json_success([
        'message' => sprintf(
            /* translators: %d: number of products moved to trash */
            __('%d produse mutate în coșul de gunoi.', 'papetarie-storefront'),
            $trashed
        ),
    ]);
}
add_action('wp_ajax_pap_aperta_purge_legacy', 'papetarie_storefront_aperta_ajax_purge_legacy');

function papetarie_storefront_aperta_ajax_backfill_attrs(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    if (!function_exists('papetarie_storefront_aperta_backfill_attributes_chunk')) {
        wp_send_json_error(['message' => __('Funcția de actualizare nu e disponibilă.', 'papetarie-storefront')], 400);
    }

    $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;

    wp_send_json_success(papetarie_storefront_aperta_backfill_attributes_chunk($offset, 300));
}
add_action('wp_ajax_pap_aperta_backfill_attrs', 'papetarie_storefront_aperta_ajax_backfill_attrs');

function papetarie_storefront_aperta_ajax_fix_menu_order(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    if (!function_exists('papetarie_storefront_aperta_fix_menu_order')) {
        wp_send_json_error(['message' => __('Funcția de reparare nu e disponibilă.', 'papetarie-storefront')], 400);
    }

    $result = papetarie_storefront_aperta_fix_menu_order();

    $message = sprintf(
        /* translators: %d: number of ordered subcategories */
        __('%d subcategorii reordonate.', 'papetarie-storefront'),
        $result['ordered']
    );

    if (!empty($result['reparented'])) {
        $message .= ' ' . sprintf(
            /* translators: %s: comma-separated slug list */
            __('Reparentate din categoria coruptă: %s.', 'papetarie-storefront'),
            implode(', ', $result['reparented'])
        );
    }

    if (!empty($result['missing'])) {
        $message .= ' ' . sprintf(
            /* translators: %d: number of slugs not found */
            __('%d slug-uri din lista de ordonare nu au fost găsite (posibil redenumite de sincronizare) — verifică manual.', 'papetarie-storefront'),
            count($result['missing'])
        );
    }

    wp_send_json_success(['message' => $message, 'result' => $result]);
}
add_action('wp_ajax_pap_aperta_fix_menu_order', 'papetarie_storefront_aperta_ajax_fix_menu_order');

/**
 * Curatenie unica: muta in cosul de gunoi produsele care nu se regasesc in
 * lista curatata manual (Excel Lavinia) - identificate prin cod_produs/sku,
 * NU prin post ID (ID-urile difera intre local si staging). Nu e un
 * mecanism permanent, doar un instrument de rulat o data prin AJAX direct
 * (fara buton in UI), la fel ca celelalte actiuni din acest fisier.
 */
function papetarie_storefront_aperta_ajax_trash_by_code(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    $itemsJson = isset($_POST['items']) ? wp_unslash((string) $_POST['items']) : '';
    $items = json_decode($itemsJson, true);

    if (!is_array($items)) {
        wp_send_json_error(['message' => __('Listă invalidă.', 'papetarie-storefront')], 400);
    }

    $trashed = 0;
    $notFound = 0;
    $alreadyTrashed = 0;

    foreach ($items as $item) {
        $codProdus = trim((string) ($item['cod_produs'] ?? ''));
        $sku = trim((string) ($item['sku'] ?? ''));

        $productId = null;

        if ($codProdus !== '') {
            $productId = papetarie_storefront_aperta_find_parent_by_cod_produs($codProdus);
        }

        if ($productId === null && $sku !== '') {
            $foundId = papetarie_storefront_aperta_find_by_sku_meta($sku);
            if ($foundId !== null) {
                $post = get_post($foundId);
                $productId = ($post && $post->post_parent) ? (int) $post->post_parent : $foundId;
            }
        }

        if ($productId === null) {
            $notFound++;
            continue;
        }

        $post = get_post($productId);
        if (!$post || $post->post_status === 'trash') {
            $alreadyTrashed++;
            continue;
        }

        wp_trash_post($productId);
        $trashed++;
    }

    wp_send_json_success([
        'trashed' => $trashed,
        'not_found' => $notFound,
        'already_trashed' => $alreadyTrashed,
        'total' => count($items),
    ]);
}
add_action('wp_ajax_pap_aperta_trash_by_code', 'papetarie_storefront_aperta_ajax_trash_by_code');

function papetarie_storefront_aperta_ajax_get_cleanup_list(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    wp_send_json_success(['items' => papetarie_storefront_aperta_lavinia_cleanup_codes()]);
}
add_action('wp_ajax_pap_aperta_get_cleanup_list', 'papetarie_storefront_aperta_ajax_get_cleanup_list');

/**
 * Restaureaza din cosul de gunoi produse identificate prin cod_produs -
 * folosit pentru cazurile 100% sigure (nume identic cu un produs deja
 * pastrat = variantа de culoare a aceleiasi linii), nu pentru revizuire
 * manuala pe scor.
 */
function papetarie_storefront_aperta_ajax_restore_by_code(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    $codesJson = isset($_POST['codes']) ? wp_unslash((string) $_POST['codes']) : '';
    $codes = json_decode($codesJson, true);

    if (!is_array($codes)) {
        wp_send_json_error(['message' => __('Listă invalidă.', 'papetarie-storefront')], 400);
    }

    $restored = 0;
    $notFound = 0;

    foreach ($codes as $codProdus) {
        $codProdus = trim((string) $codProdus);
        if ($codProdus === '') {
            continue;
        }

        // NU folosim papetarie_storefront_aperta_find_parent_by_cod_produs()
        // aici - foloseste post_status => 'any', care EXCLUDE 'trash' (o
        // particularitate WP_Query binecunoscuta). Cautam explicit inclusiv
        // in cosul de gunoi, fiindca exact acolo se afla produsele de restaurat.
        $ids = get_posts([
            'post_type' => 'product',
            'post_status' => ['trash', 'publish', 'draft', 'pending', 'private'],
            'meta_key' => '_pap_aperta_cod_produs',
            'meta_value' => $codProdus,
            'fields' => 'ids',
            'posts_per_page' => 1,
        ]);
        $productId = isset($ids[0]) ? (int) $ids[0] : null;

        if ($productId === null) {
            $notFound++;
            continue;
        }

        $post = get_post($productId);
        if ($post && $post->post_status === 'trash') {
            wp_untrash_post($productId);
            wp_update_post(['ID' => $productId, 'post_status' => 'publish']);
        }

        // Produsele variabile (cu variatii - ex. culoare/liniatura) au nevoie
        // ca TOATE variatiile lor sa fie restaurate odata cu parintele, altfel
        // produsul apare pe site fara nicio varianta cumparabila. Variatiile
        // sunt post_type separat (product_variation), nu se restaureaza
        // automat cand restauram parintele.
        global $wpdb;
        $variationIds = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product_variation' AND post_parent = %d AND post_status = 'trash'",
            $productId
        ));
        foreach ($variationIds as $variationId) {
            wp_untrash_post((int) $variationId);
            wp_update_post(['ID' => (int) $variationId, 'post_status' => 'publish']);
        }

        $restored++;
    }

    wp_send_json_success([
        'restored' => $restored,
        'not_found' => $notFound,
        'total' => count($codes),
    ]);
}
add_action('wp_ajax_pap_aperta_restore_by_code', 'papetarie_storefront_aperta_ajax_restore_by_code');
