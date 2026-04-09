import requests

tests = [
    ("Tai nan giao thong", "Xe may dam vao o to tai nga tu, nguoi bi thuong", 1),
    ("Chay lon tai nha dan", "Ngon lua bung phat, khoi den boc len cao", 2),
    ("Cay do", "Cay to gãy nhánh trúng ô tô, gió lớn", 3),
    ("Ngap duong sau mua", "Mua lon gay ngap duong pho tphcm", 4),
    ("Nguoi bi dot quy", "Nguoi dan bi dot quy nam nga tren duong can cap cuu", 5),
    ("Trom dot nhap nha dan", "Trom dot nhap nha dan ban dem lay tai san", 5),
]

for title, body, expected in tests:
    d = requests.post("http://localhost:8001/predict-json",
        json={"tieu_de": title, "noi_dung": body}, timeout=10).json()
    ok = "OK" if d["id_loai_su_co"] == expected else "WRONG"
    print(f'{ok} | expect={expected} got={d["id_loai_su_co"]} mucdo={d["id_muc_do"]} src={d["source"]} | {title}')
