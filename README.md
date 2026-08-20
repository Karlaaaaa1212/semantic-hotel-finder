# AUSURF112 -npool 掃描實驗（參考版）

這份不是要取代 opencode，而是給你一個「正確答案」的對照組：
用 opencode 生一份、用這份對一次，練習 QE_opencode_workflow.pdf 裡強調的
「人要 review，不要照單全收」。

## 使用方式

1. 把這個資料夾整個上傳到 nano4（例如用 VS Code 的 Remote-SSH 直接拖曳，
   或在你本機用 `scp -r ausurf112_npool_sweep karla1212@<nano4登入節點>:/work/karla1212/`）。

2. 把你真正的 `ausurf.in` 放進資料夾根目錄（跟 generate_jobs.py 同一層）。

3. 打開 `generate_jobs.py`，把 `MODULE_LOAD_LINES` 換成你自己之前
   **成功跑過** AUSURF112 用的 module load / export 設定 —— 這是唯一你
   一定要手動改的地方，其他預設值可以先照抄，之後再依實際情況調整。

4. 產生腳本（只會建立檔案，不會送出任何工作）：
   ```bash
   python3 generate_jobs.py
   ```

5. 檢查產生的東西：
   ```bash
   cat jobs/npool1.sh
   cat jobs/npool2.sh
   bash -n jobs/npool1.sh   # 只檢查語法，不執行
   ```

6. 先送一個確認能跑：
   ```bash
   sbatch jobs/npool1.sh
   # 用 squeue -u $USER 看狀態，跑完後打開 runs/npool1/qe.out 確認有正常收斂
   ```

7. 確認沒問題後再送第二個：
   ```bash
   sbatch jobs/npool2.sh
   ```

8. 兩個都跑完後解析結果：
   ```bash
   python3 parse.py
   ```
   會印出比較表，並存成 `summary.csv`。如果 energy 對不上或有 case 沒收斂，
   終端機會用文字警告你，不會默默放過。

## 為什麼只掃 npool ∈ {1, 2}

AUSURF112 這個系統只有 2 個 k 點，`-npool` 的值不能超過 k 點數，
所以掃描空間本來就只有這兩個值，不是設計得比較保守。

## 看完 summary.csv 之後

如果 npool=2 只比 npool=1 快一點點（例如 <20%），代表瓶頸不在 k 點平行，
去看 qe.out 最後面的 timer breakdown（`c_bands`、`cdiaghg` 或 `rdiaghg`、
`fft` 那幾行），哪個佔比最高，就代表下一輪該測 `-ndiag`（對角化）
還是 `-ntg`（FFT）。到那時候我可以再幫你寫下一輪的產生器。
