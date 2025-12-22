import difflib

file1 = "c:\\Users\\User\\Downloads\\Questree-main\\index.html"
file2 = "c:\\Users\\User\\Downloads\\Questree-main\\이거참고.html"

try:
    with open(file1, 'r', encoding='utf-8') as f1, open(file2, 'r', encoding='utf-8') as f2:
        lines1 = f1.readlines()
        lines2 = f2.readlines()

    diff = difflib.unified_diff(lines1, lines2, fromfile='index.html', tofile='reference.html', n=0)
    
    # Print only first 50 lines of diff to avoid huge output
    count = 0
    for line in diff:
        print(line, end='')
        count += 1
        if count > 100:
            print("\n... Truncated ...")
            break

except Exception as e:
    print(f"Error: {e}")
