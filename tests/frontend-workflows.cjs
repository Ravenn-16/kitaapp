// Run with: node tests/frontend-workflows.cjs (no npm dependencies).
const fs = require('fs');
const source = fs.readFileSync('public/app.js', 'utf8');
const assert = (condition, message) => { if (!condition) throw new Error(message); };
const React = {createElement: (type, props, ...children) => ({type, props, children})};
class DCLogic {
  setState(value, callback) {
    this.state = {...this.state, ...(typeof value === 'function' ? value(this.state) : value)};
    if (callback) callback();
  }
}
const Component = new Function('DCLogic', 'React', 'window', 'setTimeout', 'crypto', source + '\nreturn Component;')(
  DCLogic, React, {KITA_AUTH: {}}, () => {}, {randomUUID: () => '12345678-1234-1234-1234-123456789012'}
);
const app = new Component();
const product = {id: 1, name: 'Mask', category: 'Medical', status: 'Active', price: 10, unitPrice: 3.25, stock: 4, barcode: 'MASK1'};
app.state = {...app.state, data: {BUSINESS_DATE: '2026-09-15', PRODUCTS: [product], CATEGORIES: [{name: 'Medical', status: 'Active'}], USERS: {cashier: [], manager: [], admin: [], superadmin: []}, SUPPLIERS: [], TRANSACTIONS: []},
  productsLocal: [product], categoriesLocal: [], suppliersLocal: [], accountsLocal: [], purchaseOrdersLocal: [], receivingLocal: [], adjustmentsLocal: []};
app.setRegField('regQuantity')({target: {value: '4'}});
app.setRegField('regUnitPrice')({target: {value: '3.25'}});
assert(app.state.regCostPrice === '13.00', 'Registration cost must update from both fields');
app.setRegField('regQuantity')({target: {value: '-1'}});
assert(app.state.regCostPrice === '', 'Negative quantity must not produce a valid cost');
app.setRegField('regQuantity')({target: {value: '0'}});
assert(app.state.regCostPrice === '0.00', 'A product awaiting purchase must support zero initial stock');
app.state.role='manager';app.state.screen='mgrCategories';
assert(app.renderVals().navGroups.some(group=>group.items.some(item=>item.key==='mgrCategories')), 'Managers must have Categories in the sidebar');
assert(JSON.stringify(app.buildMgrCategories()).includes('+ Add Category'), 'The category page must offer category creation');
const selectedCategoryNav=app.renderVals().navGroups.flatMap(group=>group.items).find(item=>item.key==='mgrCategories');
assert(selectedCategoryNav.current==='page' && selectedCategoryNav.className.includes('nav-item--active'), 'Current navigation must be announced and highlighted');
app.toggleSidebar();
assert(app.state.sidebarOpen && app.renderVals().sidebarClass.includes('sidebar--open'), 'Mobile navigation must open');
selectedCategoryNav.go();
assert(!app.state.sidebarOpen && app.state.screen==='mgrCategories', 'Choosing a page must close the mobile drawer');
for (const role of ['cashier','admin','superadmin']) {
  app.state.role=role;
  assert(!app.renderVals().navGroups.flatMap(group=>group.items).some(item=>item.key==='mgrCategories'), 'Redesign must preserve manager-only category navigation');
}
app.state.role='manager';
const nodes = tree => tree == null ? [] : Array.isArray(tree) ? tree.flatMap(nodes) : typeof tree === 'object' ? [tree, ...nodes(tree.props?.children ?? tree.children)] : [];
let editedRecord = null;
const recordRows = Array.from({length:25}, (_,i)=>React.createElement('tr',{key:i},[
  React.createElement('td',null,'Record '+String(i+1).padStart(2,'0')),
  React.createElement('td',null,React.createElement('button',{onClick:()=>{editedRecord=i+1;}},'Edit')),
]));
const rowCount = tree => nodes(tree).filter(node=>node.type==='tbody').flatMap(node=>nodes(node).filter(child=>child.type==='tr')).length;
let recordView=app.recordTable('test-records',['Name','Action'],recordRows);
assert(rowCount(recordView)===20,'Record tables must paginate without losing records');
nodes(recordView).find(node=>node.type==='button' && node.children?.includes('Next')).props.onClick();
recordView=app.recordTable('test-records',['Name','Action'],recordRows);
assert(rowCount(recordView)===5,'Last page must show the remaining records');
nodes(recordView).find(node=>node.type==='button' && node.children?.includes('Edit')).props.onClick();
assert(editedRecord===21,'Pagination must preserve the original record action');
nodes(recordView).find(node=>node.type==='input' && node.props.type==='search').props.onChange({target:{value:'Record 03'}});
recordView=app.recordTable('test-records',['Name','Action'],recordRows);
assert(rowCount(recordView)===1 && app.state.tableViews['test-records'].page===1,'Searching must reset the page and return matching records');
nodes(recordView).find(node=>node.type==='button' && node.children?.includes('Edit')).props.onClick();
assert(editedRecord===3,'Filtered actions must still target the matching record');
app.setTableView('test-records',{query:'Missing'});
assert(JSON.stringify(app.recordTable('test-records',['Name','Action'],recordRows)).includes('No matching records'),'Empty search must provide recovery guidance');
app.setTableView('test-records',{query:'',page:99});
assert(rowCount(app.recordTable('test-records',['Name','Action'],recordRows.slice(0,2)))===2,'A shrinking dataset must clamp the selected page');
const registrationNodes=nodes(app.buildMgrRegistration());
for(const input of registrationNodes.filter(node=>['input','select'].includes(node.type)&&node.props.id)){
  assert(registrationNodes.some(node=>node.type==='label'&&node.props.htmlFor===input.props.id),'Registration fields must have associated labels');
}
assert(!app.validAmount('1.234') && !app.validAmount('') && !app.validAmount('-2'), 'Amounts must reject bad precision, empty and negative values');
app.state.itemPickerChecked={1:true};app.state.itemPickerFilterSupplier='';
app.addSelectedToRequest();app.setReqCartQty(0)({target:{value:'4'}});app.addSelectedToRequest();
assert(app.state.newReqCart.length === 1 && app.state.newReqCart[0].qty === 5, 'Duplicate purchase items must combine');
app.state.newReqCart[0].qty=-1;let invalidPurchaseSent=false;
const originalPurchasePost=app.authPost;app.authPost=()=>{invalidPurchaseSent=true;};
app.submitPurchase();assert(!invalidPurchaseSent, 'Invalid purchase quantity must not be submitted');
app.authPost=originalPurchasePost;app.state.newReqCart[0].qty=5;
app.state.cart=[{productId:1,unitPrice:0.05,originalPrice:0.05,qty:1,vatClass:'VATable'}];app.state.employeeDiscount=true;
assert(app.computeTotals().grandTotal===0.05,'Discount rounding must match server cents');
app.state.cart=[{productId:1,unitPrice:8.5,originalPrice:10,qty:2,vatClass:'VATable',overridden:true}];
assert(app.computeTotals().grandTotal===15.3 && app.computeTotals().discount===4.7,'Price overrides and employee discounts must compose correctly');
app.state.data.TRANSACTIONS=[{uuid:'TXN-wallet',status:'Paid',total:10,lines:[]}]; app.state.refundLookup='TXN-wallet'; app.doRefundLookup();
assert(app.state.refundTxn?.uuid==='TXN-wallet','Paid wallet transactions must be available for refund lookup');
app.state.role='admin';app.state.acctModalOpen=true;app.state.acctForm={name:'',email:'',role:'cashier',status:'Active',password:''};
const accounts=JSON.stringify(app.buildAdmManagers());
assert(!accounts.includes('Delete'),'Account screen must not offer Delete');
for (const method of ['buildMgrRegistration','buildMgrCashiers','buildSaAdmins','buildCategoryModal','buildRefunds','buildMgrPO']) app[method]();
for (const tab of ['adjustments','table','categories']) {app.state.invTab=tab;app.buildMgrInventory();}
let requests=0;
app.authPost=()=>{requests++;return new Promise(()=>{});};
app.state.cart=[{productId:1,name:'Mask',unitPrice:10,qty:1,vatClass:'VATable'}];
app.state.employeeDiscount=false;app.state.seniorApplied=false;app.state.paymentMethod='cash';
for(const value of ['', 'bad', '10invalid', '-1', '10.001', '0', '9']){
  app.state.tendered=value;
  app.startPayment();app.finalizeTransaction();
  assert(requests===0,'Invalid cash input must never be silently replaced or submitted');
}
app.state.tendered='10.00';
assert(app.cashTendered(10)===10,'Valid cash tender must retain its entered amount');
app.state.tendered='0';
assert(app.cashTendered(0)===0,'A valid zero tender on a free sale must remain zero');

app.state.regProductName='Mask';app.state.regCategory='Medical';app.state.regScannedBarcode='NEW';
app.state.regQuantity='4';app.state.regUnitPrice='3.25';app.state.regRetailPrice='10';
for(const value of ['', 'bad', '0', '-1', '2invalid', '1.001']){
  app.state.regConversionFactor=value;app.saveRegistration();
  assert(requests===0,'Invalid unit conversion must not silently default to one');
}
product.cost=13;
app.state.suppliersLocal=[{id:1,products:[]}];app.state.supMgrDetailId=1;app.state.supMgrProductChecked={1:true};
app.addSupMgrSelectedProducts();
assert(app.state.suppliersLocal[0].products[0].costPrice===3.25,'Supplier cost must use per-unit cost, not total registration cost');
app.state.promotionsLocal=[];app.state.promoSelectedItems=[1];app.state.promoType='Slow-Moving';
app.state.promoStartDate='2026-09-15';app.state.promoEndDate='2026-09-16';app.state.promoDiscountPct='10';
app.savePromotion();
assert(app.state.promotionsLocal.length===1,'Per-unit promotion cost check must not compare against initial stock total');

app.state.refundTxn={uuid:'TXN-return',lines:[{productId:1,qty:3,refundedQty:1}]};app.state.refundAction='refund';
for(const quantities of [{1:'1',2:'-1'},{1:'2.5'},{1:'3'}]){
  app.state.refundQuantities=quantities;app.submitManagerAction('refund');
  assert(requests===0,'Invalid or excessive refund quantities must fail before submission');
}
app.state.role='cashier';app.state.authenticatedUser={id:7,name:'Cashier',email:'new@example.com'};
app.state.data.TRANSACTIONS=[{uuid:'TXN-pending',date:'2026-09-15',cashierId:7,cashierRole:'cashier',cashierEmail:'old@example.com',status:'Pending Payment',total:10,paymentMode:'GCash'}];
const shift=JSON.stringify(app.buildShift());
assert(shift.includes('/payment/cancelled?uuid=TXN-pending'),'Owned pending payments must remain accessible after an email change');

app.state.paymentMethod='ewallet';app.state.paymentState='idle';
app.startPayment();app.state.paymentState='idle';app.startPayment();
assert(requests===1,'Rapid wallet checkout clicks must send a single request');
console.log('Frontend workflow checks passed, including tender/conversion validation, unit costs, refund limits, pending-payment access and duplicate wallet submissions.');

(async()=>{
  let finishFirst,finishSecond;
  const first=app.trackLoad(()=>new Promise(resolve=>{finishFirst=resolve;}));
  const second=app.trackLoad(()=>new Promise(resolve=>{finishSecond=resolve;}));
  assert(app.state.pendingLoads===2,'Concurrent loads must both be tracked');
  finishFirst('first');
  assert(await first==='first' && app.state.pendingLoads===1,'First completion must retain the other loading indicator');
  finishSecond('second');
  assert(await second==='second' && app.state.pendingLoads===0,'Final completion must clear loading feedback');
  let caught=false;
  try{await app.trackLoad(()=>Promise.reject(new Error('Load failed')));}catch(error){caught=error.message==='Load failed';}
  assert(caught && app.state.pendingLoads===0,'Loading errors must propagate and clear feedback');
  console.log('Record browsing, registration labels and concurrent loading checks passed.');
})().catch(error=>{console.error(error);process.exitCode=1;});

// Super Admin uses measured data, account lifecycle actions and no security module.
const sa = new Component();
sa.state={...sa.state,role:'superadmin',screen:'saDashboard',data:null,accountsLocal:[
  {id:1,role:'cashier',name:'Active cashier',email:'active@example.com',status:'Active',dateCreated:'2026-09-01'},
  {id:2,role:'admin',name:'Inactive admin',email:'inactive@example.com',status:'Inactive',dateCreated:null}
]};
const nav=sa.renderVals().navGroups.flatMap(group=>group.items);
assert(nav.some(item=>item.key==='saDashboard'),'Super Admin dashboard must be in navigation');
assert(!nav.some(item=>item.key==='saSecurity'),'Security Settings must not be navigable');
assert(!source.includes('buildSaSecurity')&&!source.includes('Security Settings'),'Security screen and controls must be removed');
let saAccounts=JSON.stringify(sa.buildSaAdmins());
assert(saAccounts.includes('Deactivate Account')&&saAccounts.includes('Activate Account'),'Both status actions must appear');
assert(!saAccounts.includes('Delete')&&!saAccounts.includes('Trash'),'Account UI must not offer deletion');
sa.state.acctStatusFilter='Inactive';
assert(!JSON.stringify(sa.buildSaAdmins()).includes('Active cashier'),'Status filter must exclude active users');
sa.state.acctStatusFilter='';sa.state.acctRoleFilter='cashier';
assert(!JSON.stringify(sa.buildSaAdmins()).includes('Inactive admin'),'Role filter must exclude other roles');
sa.state.saLoading=true;
assert(JSON.stringify(sa.renderVals().activeScreen).includes('Loading system performance'),'Dashboard loading must work without the POS catalog');
sa.state.saLoading=false;sa.state.saError='Unable to retrieve system performance information.';
assert(JSON.stringify(sa.buildSaDashboard()).includes('Retry'),'Errors must allow retry');
sa.state.saError='';sa.state.saDashboard={total_users:2,active_users:1,inactive_users:1,average_daily_events:0,activity_days:[{date:'2026-09-23',events:0}],recent_activity:[],application_status:'Operational',database_status:'Connected',database_response_ms:0.001,generation_ms:2.45,measured_at:'2026-09-24T12:00:00Z'};
const dashboard=JSON.stringify(sa.buildSaDashboard());
assert(dashboard.includes('No user activity data is available yet.'),'Empty activity must be explained');
assert(dashboard.includes('< 0.01 ms')&&!dashboard.includes('0 ms'),'Sub-millisecond timings must not be rounded to fake zero');
assert(!dashboard.includes('Sales')&&!dashboard.includes('Revenue'),'System dashboard must not show POS statistics');
sa.handleSessionResponse({status:401},{deactivated:true,message:'Your account has been deactivated. Please contact your operator.',csrf_token:'new-token'});
assert(sa.state.authStep==='login'&&sa.state.data===null&&sa.state.role===null,'Revocation must clear authenticated UI');
assert(sa.state.loginError==='Your account has been deactivated. Please contact your operator.','Revocation message must be exact');
console.log('Super Admin navigation, filters, status actions, dashboard states and session revocation checks passed.');

const notificationApp=new Component();
for(const role of ['cashier','manager','admin','superadmin']){
  notificationApp.state.role=role;
  assert(notificationApp.renderVals().showMgrBell,'All signed-in roles must have a notification bell');
}
notificationApp.state.mgrNotificationsLocal=[{id:1,type:'Approved',message:'PO 1 approved',created_at:'2026-09-24',read_at:null}];
notificationApp.state.notificationUnread=1;
assert(notificationApp.renderVals().mgrNotifUnreadCount===1,'Unread count must use the server total');
assert(notificationApp.renderVals().mgrNotifItems[0].className.includes('unread'),'Unread notices must be highlighted');
notificationApp.state.mgrNotificationsLocal[0].read_at='2026-09-24';
assert(!notificationApp.renderVals().mgrNotifItems[0].className.includes('unread'),'Saved read state must remove unread highlight');
console.log('Notification bell visibility, server unread count and persisted read styling checks passed.');
