"""
Questree OCR 매크로 - 화면 인식 + 실제 마우스 드래그
보안 테스트 목적!
"""
import time
import pyautogui
import pytesseract
from PIL import Image, ImageGrab
import re

# Tesseract 경로 (설치 필요)
pytesseract.pytesseract.tesseract_cmd = r'C:\Program Files\Tesseract-OCR\tesseract.exe'

# 설정
DRAG_DELAY = 0.15  # 드래그 간격 (50ms 제한 우회, 0.1초 이상)

def capture_game_area():
    """게임 영역 스크린샷"""
    # 전체 화면 캡처
    screenshot = ImageGrab.grab()
    screenshot.save("game_screenshot.png")
    return screenshot

def find_items_on_screen():
    """화면에서 단어 아이템 찾기"""
    screenshot = capture_game_area()
    
    # OCR로 텍스트 인식 (한국어)
    text = pytesseract.image_to_string(screenshot, lang='kor')
    print(f"[OCR] 인식된 텍스트:\n{text[:200]}...")
    
    # 각 아이템의 위치 찾기
    items = []
    boxes = pytesseract.image_to_boxes(screenshot, lang='kor')
    
    # 단어와 위치 매핑 (간단한 방식)
    data = pytesseract.image_to_data(screenshot, lang='kor', output_type=pytesseract.Output.DICT)
    
    for i, txt in enumerate(data['text']):
        if txt.strip() and len(txt) > 0:
            x = data['left'][i]
            y = data['top'][i]
            w = data['width'][i]
            h = data['height'][i]
            items.append({
                'text': txt,
                'x': x + w // 2,
                'y': y + h // 2
            })
    
    return items

def sort_and_drag(items):
    """아이템을 가나다순으로 드래그"""
    if not items:
        print("[!] 아이템을 찾지 못함")
        return
    
    # 가나다순 정렬
    sorted_items = sorted(items, key=lambda x: x['text'])
    
    print(f"[*] {len(sorted_items)}개 아이템 정렬 시작...")
    
    for i, item in enumerate(sorted_items):
        target_y = 200 + (i * 50)  # 목표 Y 위치 (대략적)
        
        # 마우스 드래그
        pyautogui.moveTo(item['x'], item['y'], duration=0.1)
        pyautogui.mouseDown()
        time.sleep(0.05)
        pyautogui.moveTo(item['x'], target_y, duration=0.1)
        pyautogui.mouseUp()
        
        print(f"  [{i+1}/{len(sorted_items)}] '{item['text']}' 이동")
        time.sleep(DRAG_DELAY)  # 50ms 제한 우회
    
    print("[*] 정렬 완료!")

def main():
    print("=" * 50)
    print("OCR 매크로 테스트")
    print("5초 후 시작 - 게임 화면을 열어두세요!")
    print("=" * 50)
    
    time.sleep(5)
    
    print("\n[1] 화면 캡처...")
    items = find_items_on_screen()
    
    print(f"[2] {len(items)}개 아이템 발견")
    
    print("[3] 자동 정렬 시작...")
    sort_and_drag(items)
    
    print("\n[*] 완료!")

if __name__ == "__main__":
    main()
