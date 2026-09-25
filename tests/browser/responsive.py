"""Run against a local Laravel server. Uses synthetic data and intercepts API writes.

python tests/browser/responsive.py
Optional: KITA_TEST_URL (default http://127.0.0.1:8765).
Install Python Playwright and use an installed Chrome browser.
"""
import sys, os, json, subprocess
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2]
sys.path.insert(0,str(ROOT/'.tmp/responsive-tools'))
from playwright.sync_api import sync_playwright

OUT=ROOT/'.tmp/responsive-results'
OUT.mkdir(parents=True,exist_ok=True)
subprocess.run(['php',str(ROOT/'tests/browser/render-report.php')],cwd=ROOT,check=True)
SIZES=[(320,740),(375,812),(425,900),(768,1024),(1024,768),(1366,768),(1440,900),(1920,1080),(812,375)]
SCREENS={
 'cashier':['pos','refunds','shift'],
 'manager':['mgrDashboard','mgrRequest','mgrRequestView','mgrPurchaseHistory','mgrPO','mgrInventory','mgrCategories','mgrRegistration','archive','mgrCashiers','mgrPromotions','mgrSupplier'],
 'admin':['admDashboard','admPurchasedOrders','admForwarded','admDisapproved','admReceivingApprovals','admInventoryApprovals','admSuppliers','admManagers','admReports'],
 'superadmin':['saDashboard','saAdmins','saRoles','saBackup','saAudit']}
VARIANTS=[
 *[(f'inventory-{tab}','mgrInventory','manager',json.dumps({'invTab':tab})) for tab in ['adjustments','writeoffs','recall','reconciliation','expiry','categories']],
 ('seasonal-promotion','mgrPromotions','manager',"{promoType:'Seasonal'}"),
 ('category-promotion','mgrPromotions','manager',"{promoType:'Category-Wide'}"),
 ('supplier-history','mgrSupplier','manager',"{supMgrScreen:'detail',supMgrDetailId:1,supMgrDetailTab:'history'}"),
 ('damage-product-picker','mgrInventory','manager',"{addDamageOpen:true,damagePickerSearch:'',damagePickerOpen:true,addDamageForm:{productId:'1',qty:'2'}}"),
 ('cart','pos','cashier',"{cart:__fixture.products.slice(0,3).map(p=>({...p,lineId:p.id,qty:1,unitPrice:p.price}))}"),
 ('receipt','pos','cashier','{transactionResult:__fixture.transaction}'),
 ('refund-form','refunds','cashier',"{refundTxn:__fixture.transaction,refundAction:'refund'}"),
 ('request-details','mgrRequestView','manager','{purchaseRequestDetail:__fixture.requests[0].id}'),
 ('new-product','mgrRequest','manager','{purchaseProductOpen:true}'),
 ('request-cart','mgrRequest','manager','{newReqCart:__fixture.order.lines}'),
 ('review-edit','admPurchasedOrders','admin','{purchaseReviewId:__fixture.requests[0].id,purchaseReviewLines:__fixture.requests[0].lines}'),
 ('receiving-form','mgrPO','manager',"{purchaseReceivingId:__fixture.order.id,receiveQty:{},receiveReference:'',receiveDate:'2026-09-25'}"),
 ('item-picker','mgrRequest','manager','{itemPickerOpen:true}'),
 ('manual-lookup','pos','cashier','{manualLookupOpen:true}'),
 ('senior-discount','pos','cashier','{seniorPwdOpen:true}'),
 ('override','pos','cashier','{overrideLine:1}'),
 ('category-dialog','mgrCategories','manager','{categoryModalOpen:true}'),
 ('account-dialog','mgrCashiers','manager','{acctModalOpen:true}'),
 ('supplier-dialog','mgrSupplier','manager','{supMgrModalOpen:true}'),
 ('supplier-detail','mgrSupplier','manager',"{supMgrScreen:'detail',supMgrDetailId:1}"),
 ('supplier-picker','mgrSupplier','manager',"{supMgrScreen:'detail',supMgrDetailId:1,supMgrProductPickerOpen:true}"),
 ('promo-picker','mgrPromotions','manager','{promoPickerOpen:true}'),
 ('damage-dialog','mgrInventory','manager',"{addDamageOpen:true,damagePickerSearch:''}"),
 ('price-dialog','mgrInventory','manager',"{priceEditOpen:true,priceEditTarget:{productId:1,field:'price'}}"),
 ('archive-dialog','archive','manager','{archiveConfirmOpen:true}'),
 ('notifications','mgrDashboard','manager',"{mgrNotifOpen:true,mgrNotificationsLocal:[{id:1,type:'Purchase update',message:'Long notification '+ 'REFERENCE'.repeat(30),created_at:'2026-09-25',is_read:false}]}"),
]

MEASURE="""() => {
 const vw=innerWidth, visible=e=>e.getClientRects().length&&getComputedStyle(e).visibility!=='hidden';
 const failures=[];
 for(const e of document.querySelectorAll('.main-shell,.content-area,.kita-card,.sa-panel,.form-section,.table-scroll,.kita-modal-overlay>div,.notif-menu,.auth-card')){
   if(!visible(e))continue;
   const r=e.getBoundingClientRect();
   if(r.left < -1 || r.right>vw+1)failures.push({type:'bounds',tag:e.className,left:r.left,right:r.right});
   if(e.scrollWidth>e.clientWidth+2 && !['auto','scroll'].includes(getComputedStyle(e).overflowX))failures.push({type:'overflow',tag:e.className,scroll:e.scrollWidth,width:e.clientWidth});
 }
 for(const e of document.querySelectorAll('.kita-modal-overlay>div')){
   const r=e.getBoundingClientRect();if(r.top < -1 || r.bottom>innerHeight+1)failures.push({type:'modal-height',top:r.top,bottom:r.bottom});
 }
 if(document.documentElement.scrollWidth>vw+1)failures.push({type:'page-overflow',width:document.documentElement.scrollWidth});
 return failures;
}"""

with sync_playwright() as p:
 browser=p.chromium.launch(channel='chrome',headless=True)
 page=browser.new_page()
 errors=[]; results=[]
 page.on('pageerror',lambda error:errors.append(str(error)))
 page.on('console',lambda msg:print('CONSOLE: '+msg.text[:300],flush=True) if msg.type=='error' and 'resource' not in msg.text else None)
 page.route('**/app.js*',lambda route:route.fulfill(body=(ROOT/'public/app.js').read_text(encoding='utf-8').replace('componentDidMount() {','componentDidMount() { window.__app=this;'),content_type='application/javascript'))
 page.route('**/api/**',lambda route:route.fulfill(json={'data':[],'unread_count':0,'notifications':[],'accounts':[]}))
 # No credentials reach the server while checking keyboard submission.
 login_requests=[]
 def login(route):
  login_requests.append(route.request.post_data_json)
  route.fulfill(status=422,json={'message':'Test sign-in received'})
 page.route('**/login',login)
 page.goto(os.getenv('KITA_TEST_URL','http://127.0.0.1:8765'),wait_until='networkidle')
 page.wait_for_function('!!window.__app',timeout=60000)
 for width,height in SIZES:
  page.set_viewport_size({'width':width,'height':height})
  results.append({'screen':'login','width':width,'height':height,'issues':page.evaluate(MEASURE)})
 page.locator('#login-email').fill('responsive.test@example.com')
 page.locator('#login-password').fill('test-password')
 page.locator('#login-password').press('Enter')
 page.get_by_role('alert').filter(has_text='Test sign-in received').wait_for()
 assert len(login_requests)==1,login_requests
 page.evaluate((ROOT/'tests/browser/responsive-fixture.js').read_text(encoding='utf-8'))
 for width,height in SIZES:
  page.set_viewport_size({'width':width,'height':height})
  scenarios=[(screen,screen,role,'{}') for role,screens in SCREENS.items() for screen in screens]+VARIANTS
  for name,screen,role,extra in scenarios:
   before=len(errors)
   try:
    page.evaluate(f"__showScreen({json.dumps(screen)},{json.dumps(role)},{extra})")
    issues=page.evaluate(MEASURE)
    if not page.locator('.content-area').inner_text().strip(): issues.append({'type':'empty-screen'})
    issues += [{'type':'runtime','message':e} for e in errors[before:]]
   except Exception as error:
    issues=[{'type':'exception','message':str(error)[:400]}]
   results.append({'screen':name,'width':width,'height':height,'issues':issues})
   if issues: print(json.dumps(results[-1]),flush=True)
   if width in (375,1440) and name in ('mgrDashboard','mgrPurchaseHistory','receiving-form','item-picker','saDashboard','pos','mgrPromotions','mgrRegistration'):
    page.screenshot(path=str(OUT/f'{name}-{width}.png'))
  page.evaluate("__showScreen('mgrDashboard','manager')")
  if width<1024:
   page.locator('#sidebar-toggle').click()
   assert page.locator('.sidebar').evaluate("e=>getComputedStyle(e).visibility==='visible'")
   assert page.locator('.main-shell').get_attribute('inert') is not None
   page.keyboard.press('Shift+Tab')
   assert page.locator('.logout-button').evaluate('e=>e===document.activeElement')
   page.keyboard.press('Tab')
   assert page.locator('.drawer-close').evaluate('e=>e===document.activeElement')
   page.keyboard.press('Escape')
   assert page.locator('#sidebar-toggle').get_attribute('aria-expanded')=='false'
   assert page.locator('#sidebar-toggle').evaluate('e=>e===document.activeElement')
  print(f'Checked {width}x{height}',flush=True)
 page.evaluate("__showScreen('mgrPurchaseHistory','manager')")
 page.emulate_media(media='print')
 assert not page.locator('.sidebar').is_visible()
 assert not page.locator('.topbar').is_visible()
 assert page.locator('.table-scroll').first.evaluate("e=>getComputedStyle(e).overflowX==='visible'")
 page.pdf(path=str(OUT/'purchase-history.pdf'),format='A4',landscape=True)
 page.emulate_media(media='screen')
 page.route('**/responsive-report-test',lambda route:route.fulfill(body=(OUT/'report.html').read_text(encoding='utf-8'),content_type='text/html'))
 base=os.getenv('KITA_TEST_URL','http://127.0.0.1:8765')
 for path in ['/payment/success','/payment/cancelled','/responsive-report-test']:
  page.goto(base+path,wait_until='networkidle')
  for width,height in SIZES:
   page.set_viewport_size({'width':width,'height':height})
   issues=page.evaluate(MEASURE)
   results.append({'screen':path,'width':width,'height':height,'issues':issues})
   if issues:print(json.dumps(results[-1]),flush=True)
  if path=='/responsive-report-test':
   page.set_viewport_size({'width':375,'height':812})
   page.screenshot(path=str(OUT/'purchase-report-375.png'),full_page=True)
   assert page.locator('.table-wrap').count()==3
   page.emulate_media(media='print')
   assert not page.locator('.toolbar').is_visible()
   assert page.locator('.table-wrap').first.evaluate("e=>getComputedStyle(e).overflowX==='visible'")
   page.pdf(path=str(OUT/'purchase-report.pdf'),format='A4')
 (OUT/'results.json').write_text(json.dumps({'checks':results,'runtime_errors':errors},indent=2),encoding='utf-8')
 failed=[r for r in results if r['issues']]
 print(json.dumps({'checks':len(results),'failed':len(failed),'runtime_errors':errors,'login_enter_requests':len(login_requests)}),flush=True)
 browser.close()
 sys.exit(1 if failed else 0)
