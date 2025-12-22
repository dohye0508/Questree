import re

file_path = 'c:\\Users\\User\\Downloads\\Questree-main\\index.html'

# Correct content for achDefs
ach_defs_content = """    const achDefs = [
        { id: 'clear_easy', icon: '🚩', name: '하남자 정복', desc: '하남자 난이도 클리어' },
        { id: 'speed_easy', icon: '👧', name: '하남자의 왕', desc: '10초 이내 클리어' },
        { id: 'clear_normal', icon: '🚩', name: '중남자 정복', desc: '중남자 난이도 클리어' },
        { id: 'speed_normal', icon: '👦', name: '중남자의 왕', desc: '60초 이내 클리어' },
        { id: 'clear_hard', icon: '🚩', name: '상남자 정복', desc: '상남자 난이도 클리어' },
        { id: 'speed_hard', icon: '😎', name: '상남자의 왕', desc: '180초 이내 클리어' },
        { id: 'clear_extreme', icon: '🤫', name: '씹상남자 정복', desc: '씹상남자 난이도 클리어' },
        { id: 'speed_extreme', icon: '👑', name: '씹상남자의 왕', desc: '600초 이내 클리어' },
        { id: 'god_hand', icon: '🎯', 'name': '신의 손', 'desc': '진행도를 낮추지 않고 완벽 클리어' },
        { id: 'slow_steady', icon: '🔥', 'name': '불굴의 의지', 'desc': '오랜 시간(난이도별 기준) 끝에 승리' },
        { id: 'ranker', icon: '🏆', 'name': '명예의 전당', 'desc': '랭킹 Top 5 진입' },
        { id: 'goat', icon: '🐐', 'name': 'GOAT', 'desc': '랭킹 1위 달성' },
        { id: 'lucky_seven', icon: '🍀', 'name': '럭키세븐', 'desc': '기록 .77초 달성' },
        { id: 'veteran_10', icon: '⚔️', 'name': '전장의 지배자', 'desc': '10회 클리어 달성' },
        { id: 'real_man', icon: '☠️', 'name': '남자중의 남자', 'desc': '업적 10개 달성' },
        { id: 'pvp_first_win', icon: '🥊', 'name': '첫 승리', 'desc': 'PVP 첫 승리' },
        { id: 'pvp_10_wins', icon: '🎖️', 'name': 'PVP 마스터', 'desc': 'PVP 10승 달성' },
        { id: 'pvp_50_wins', icon: '💀', 'name': 'PVP 전설', 'desc': 'PVP 50승 달성' },
        { id: 'pvp_5_streak', icon: '🛡️', 'name': '불패', 'desc': 'PVP 5연승' },
        { id: 'secret_master', icon: '❓', 'name': '???', 'desc': '???', unlockedIcon: '🔱', unlockedName: '신', unlockedDesc: '모든 업적 달성' },
    ];"""

# Correct content for ACHIEVEMENT_AVATARS
ach_avatars_content = """const ACHIEVEMENT_AVATARS = [
    { emoji: '🚩', achId: 'clear_easy', name: '하남자 정복' },
    { emoji: '👧', achId: 'speed_easy', name: '하남자의 왕' },
    { emoji: '👦', achId: 'speed_normal', name: '중남자의 왕' },
    { emoji: '😎', achId: 'speed_hard', name: '상남자의 왕' },
    { emoji: '🤫', achId: 'clear_extreme', name: '씹상남자 정복' },
    { emoji: '👑', achId: 'speed_extreme', name: '씹상남자의 왕' },
    { emoji: '🎯', achId: 'god_hand', name: '신의 손' },
    { emoji: '🔥', achId: 'slow_steady', name: '불굴의 의지' },
    { emoji: '🏆', achId: 'ranker', name: '명예의 전당' },
    { emoji: '🐐', achId: 'goat', name: 'GOAT' },
    { emoji: '🍀', achId: 'lucky_seven', name: '럭키세븐' },
    { emoji: '⚔️', achId: 'veteran_10', name: '전장의 지배자' },
    { emoji: '☠️', achId: 'real_man', name: '남자중의 남자' },
    { emoji: '🥊', achId: 'pvp_first_win', name: '첫 승리' },
    { emoji: '🎖️', achId: 'pvp_10_wins', name: 'PVP 마스터' },
    { emoji: '💀', achId: 'pvp_50_wins', name: 'PVP 전설' },
    { emoji: '🛡️', achId: 'pvp_5_streak', name: '불패' },
    { emoji: '🔱', achId: 'secret_master', name: '신', lockedEmoji: '❓', lockedName: '???' },
];"""

try:
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Regex to replace achDefs using simpler bounds
    # Match from "const achDefs = [" to "];"
    content = re.sub(r'const\s+achDefs\s*=\s*\[.*?\];', ach_defs_content, content, flags=re.DOTALL)
    
    # Regex to replace ACHIEVEMENT_AVATARS
    content = re.sub(r'const\s+ACHIEVEMENT_AVATARS\s*=\s*\[.*?\];', ach_avatars_content, content, flags=re.DOTALL)

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
        
    print("Successfully patched index.html")

except Exception as e:
    print(f"Error: {e}")
