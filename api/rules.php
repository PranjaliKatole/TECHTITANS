<?php
// Issue checklist: category => [type => danger weight added to priority score]
const ISSUE_TYPES = [
  'Road/Pothole'   => ['Pothole'=>8,'Open manhole'=>25,'Road cave-in'=>22,'Waterlogged road'=>12,'Broken footpath'=>5,'Damaged speed breaker'=>6,'Missing road sign'=>6],
  'Streetlight'    => ['Light not working'=>5,'Flickering light'=>2,'Light on in daytime'=>1,'Pole damaged or leaning'=>15,'Exposed live wire'=>28],
  'Garbage'        => ['Overflowing bin'=>5,'Garbage not collected'=>6,'Illegal dumping'=>6,'Dead animal'=>12,'Burning waste'=>15],
  'Water/Drainage' => ['Pipe leak'=>8,'Blocked drain'=>8,'Low water pressure'=>4,'No water supply'=>10,'Sewage overflow'=>15,'Contaminated water'=>20],
  'Other'          => [],
];
const DEPARTMENTS = [
  'Road/Pothole'=>'Roads Dept','Streetlight'=>'Electrical Dept',
  'Garbage'=>'Sanitation Dept','Water/Drainage'=>'Water Works','Other'=>'General Services',
];
const SLA_HOURS = ['Critical'=>24,'High'=>48,'Medium'=>96,'Low'=>168];

function validType($cat, $type) {
  return ($type && isset(ISSUE_TYPES[$cat][$type])) ? $type : null;
}
function typeWeight($cat, $type) { return ISSUE_TYPES[$cat][$type] ?? 0; }
function deptFor($cat) { return DEPARTMENTS[$cat] ?? 'General Services'; }
function dueFor($sev, $from = null) {
  return date('Y-m-d H:i:s', ($from ?: time()) + (SLA_HOURS[$sev] ?? 168) * 3600);
}