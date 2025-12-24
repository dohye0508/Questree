"""
Questree 매크로 테스트 스크립트
보안 테스트 목적으로 제작됨 - 실제 사용 금지!
"""
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.edge.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# 설정
URL = "https://ishstree.dothome.co.kr/"
USER_ID = "jetski"  # 아이디
PASSWORD = "jetski"  # 비밀번호

def setup_driver():
    """브라우저 설정"""
    options = Options()
    # DevTools 감지 우회 시도
    options.add_argument("--disable-blink-features=AutomationControlled")
    options.add_experimental_option("excludeSwitches", ["enable-automation"])
    
    driver = webdriver.Edge(options=options)
    driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
    return driver

def login(driver):
    """로그인"""
    wait = WebDriverWait(driver, 10)
    
    # 아이디 입력
    id_input = wait.until(EC.presence_of_element_located((By.ID, "inputId")))
    id_input.clear()
    id_input.send_keys(USER_ID)
    
    # 비밀번호 입력
    pw_input = driver.find_element(By.ID, "inputPw")
    pw_input.clear()
    pw_input.send_keys(PASSWORD)
    
    # 로그인 버튼 클릭
    login_btn = driver.find_element(By.ID, "authActionBtn")
    login_btn.click()
    time.sleep(1)

def start_game(driver, difficulty=10):
    """게임 시작"""
    wait = WebDriverWait(driver, 10)
    
    # 로그인 완료 대기
    time.sleep(2)
    
    # 1. 난이도 선택
    driver.execute_script(f"document.getElementById('sizeSel').value = '{difficulty}'")
    time.sleep(0.5)
    
    # 2. "실험 시작" 버튼 클릭 → 모드 선택 모달이 뜸
    driver.execute_script("document.getElementById('startBtn').click()")
    time.sleep(1)
    
    # 3. 모드 선택 모달에서 "혼자 하기" 클릭
    driver.execute_script("""
        var modal = document.getElementById('modeSelectModal');
        if(modal && !modal.classList.contains('hidden')) {
            startSoloGame();
        }
    """)
    time.sleep(3)  # 게임 로딩 대기

def get_items_sorted(driver):
    """아이템들을 가나다순으로 정렬된 순서로 가져오기"""
    items = driver.find_elements(By.CSS_SELECTOR, "#list .item")
    item_data = []
    for item in items:
        label = item.find_element(By.CSS_SELECTOR, ".label").text
        item_data.append((label, item))
    
    # 가나다순 정렬
    item_data.sort(key=lambda x: x[0])
    return [x[1] for x in item_data]

def auto_sort(driver):
    """자동 정렬 시도 (Sortable.js 드래그 시뮬레이션)"""
    
    print("[*] Sortable.js 드래그 이벤트 시뮬레이션...")
    try:
        result = driver.execute_script("""
            const items = [...document.querySelectorAll('#list .item')];
            if(items.length === 0) return "아이템 없음";
            
            // 가나다 순으로 정렬 순서 계산
            const sorted = [...items].sort((a,b) => {
                const textA = a.querySelector('.label').textContent;
                const textB = b.querySelector('.label').textContent;
                return textA.localeCompare(textB, 'ko');
            });
            
            // Sortable을 통해 드래그 이벤트 발생시키며 정렬
            const list = document.getElementById('list');
            
            async function moveItem(fromIdx, toIdx) {
                return new Promise(resolve => {
                    // dragging 플래그 설정 (보안 우회 시도)
                    if(typeof dragging !== 'undefined') dragging = true;
                    
                    const item = items[fromIdx];
                    const target = list.children[toIdx];
                    
                    // 드래그 이벤트 시뮬레이션
                    const dragStart = new DragEvent('dragstart', {bubbles: true});
                    const dragEnd = new DragEvent('dragend', {bubbles: true});
                    
                    item.dispatchEvent(dragStart);
                    
                    setTimeout(() => {
                        list.insertBefore(item, target);
                        item.dispatchEvent(dragEnd);
                        if(typeof dragging !== 'undefined') dragging = false;
                        resolve();
                    }, 100);
                });
            }
            
            // 순차적으로 아이템 이동
            (async () => {
                for(let i = 0; i < sorted.length; i++) {
                    const currentIdx = [...list.children].indexOf(sorted[i]);
                    if(currentIdx !== i) {
                        await moveItem(currentIdx, i);
                        await new Promise(r => setTimeout(r, 100));
                    }
                }
            })();
            
            return "드래그 시뮬레이션 시작!";
        """)
        print(f"  결과: {result}")
    except Exception as e:
        print(f"  오류: {e}")
    
    time.sleep(5)  # 정렬 완료 대기
    
    # 결과 확인
    try:
        item_count = driver.execute_script("return document.querySelectorAll('#list .item').length")
        is_sorted = driver.execute_script("return typeof isSortedKeys === 'function' && isSortedKeys(currentKeys())")
        print(f"  아이템 수: {item_count}, 정렬됨: {is_sorted}")
    except Exception as e:
        print(f"  상태 확인 오류: {e}")

def check_result(driver):
    """결과 확인"""
    try:
        # 완료 모달 확인
        finish_modal = driver.find_element(By.ID, "finishModal")
        if "hidden" not in finish_modal.get_attribute("class"):
            print("[✓] 게임 완료됨!")
            time.sleep = driver.find_element(By.ID, "finishTimeVal").text
            print(f"    기록: {time}")
            return True
    except:
        pass
    
    print("[✗] 게임이 완료되지 않음 (보안에 막힘?)")
    return False

def main():
    print("=" * 50)
    print("Questree 매크로 테스트")
    print("보안 테스트 목적 - 실제 사용 금지!")
    print("=" * 50)
    
    driver = setup_driver()
    
    try:
        print("\n[1] 사이트 접속...")
        driver.get(URL)
        time.sleep(2)
        
        print("[2] 로그인...")
        login(driver)
        
        # 디버깅: 현재 상태 확인
        print("  [DEBUG] 로그인 모달 상태:", driver.execute_script("return document.getElementById('loginModal').classList.contains('hidden')"))
        print("  [DEBUG] 모드선택 모달 상태:", driver.execute_script("return document.getElementById('modeSelectModal')?.classList.contains('hidden')"))
        
        print("[3] 게임 시작 (난이도 10)...")
        start_game(driver, 10)
        
        # 디버깅: 게임 상태 확인
        print("  [DEBUG] 게임 시작 여부:", driver.execute_script("return typeof started !== 'undefined' && started"))
        print("  [DEBUG] 아이템 개수:", driver.execute_script("return document.querySelectorAll('#list .item').length"))
        
        print("[4] 자동 정렬 시도...")
        auto_sort(driver)
        
        print("[5] 결과 확인...")
        check_result(driver)
        
        print("\n[*] 테스트 완료! 브라우저를 닫으려면 Enter...")
        input()
        
    except Exception as e:
        print(f"\n[!] 오류 발생: {e}")
        import traceback
        traceback.print_exc()
        input("Enter를 눌러 종료...")
    
    finally:
        driver.quit()

if __name__ == "__main__":
    main()
