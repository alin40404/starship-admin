# Admin 后台字号汇总

> 主题基准：`preferences.theme.fontSize = 16` → `--font-size-base: 16px`  
> 口径：**动态** = `calc(var(--font-size-base) * n / 16)` 或组件 size；**写死配置** = 仅主题基准本身可改。  
> AI 规则：`.cursor/rules/admin-font-size.mdc`

## 基准 Token

| Token | 公式 | base=16 结果 | 用途 |
|---|---|---|---|
| `--font-size-base` | 主题配置 | **16px** | 全局基准 |
| `--admin-font-search` | `base × 13/16` | **13px** | 搜索输入、筛选面板控件 |
| `--admin-font-search-sm` | `base × 12/16` | **12px** | 浮动 label 上浮、筛选面板 label |
| `--admin-font-search-xs` | `base × 11/16` | **11px** | 筛选角标 |
| `--vben-button-font-size` / `--admin-font-control` | `base × 13/16` | **13px** | 默认按钮、表单字段/label、表格 Tag/Switch/操作链接/Tooltip |

定义位置：`packages/@core/base/design/src/design-tokens/default.css`

---

## 1. 搜索表单（动态，目标档 13 / 12 / 11）

| 元素 | base=16 结果 | 类型 | Token |
|---|---|---|---|
| 输入框 / placeholder | 13px | 动态 | `--admin-font-search` |
| Select 占位 / 选项框 | 13px | 动态 | `--admin-font-search` |
| 浮动字段名（空态） | 13px | 动态 | `--admin-font-search` |
| 浮动字段名（上浮） | 13px | 动态 | `--admin-font-search` |
| Select 无数据（暂无数据） | 13px | 动态 | `--admin-font-control`（`antd/index.css`） |
| 筛选 / 重置按钮文案 | 13px | 动态 | `--admin-font-search` |
| 筛选角标数字 | 11px | 动态 | `--admin-font-search-xs` |
| 筛选面板字段 label | 12px | 动态 | `--admin-font-search-sm` |
| 筛选面板输入 / 底部按钮 | 13px | 动态 | `--admin-font-search` |

实现：`grid-search-form.css`、`vxe-table/style.css` 使用上述 token，**不再写死 px**。

---

## 2. 列表按钮

| 元素 | base=16 结果 | 类型 | 来源 |
|---|---|---|---|
| 新增按钮 | 13px | 动态 | `--vben-button-font-size`（`base × 13/16`） |
| 批量操作按钮 | 13px | 动态 | 同上 |
| 筛选 / 重置等默认按钮 | 13px | 动态 | 同上（或搜索区 token，同为 13） |
| 工具栏图标按钮 | 无正文 | — | 图标按钮 |

按钮高度约 **32px**（Ant middle）；**字号默认一律 13（动态）**。

---

## 3. 表格

| 元素 | base=16 结果 | 类型 | 来源 |
|---|---|---|---|
| 表头 | ≈13px（字重 700） | 动态 | VXE `size: 'small'` |
| 正文（各列） | ≈13px | 动态 | 同上 |
| 状态 Tag | 13px | 动态 | `--admin-font-control` |
| 状态 Switch（已启用等） | 13px | 动态 | `--admin-font-control` |
| 操作链接（修改/删除） | 13px | 动态 | `--vben-button-font-size`（ant-btn） |
| Tooltip 提示 | 13px | 动态 | `--admin-font-control` |
| 分页文案 | ≈13px | 动态 | VXE pager |

表格档位配置：`adapter/vxe-table.ts` → `grid.size: 'small'`（不要再单独写死 13px）。

---

## 4. 添加 / 编辑表单

| 元素 | base=16 结果 | 类型 | 来源 |
|---|---|---|---|
| 弹窗标题 | ≈16px | 动态 | 接近 base（标题档，非控件） |
| 字段 label | 13px | 动态 | `--admin-font-control` |
| 输入框文字 / placeholder | 13px | 动态 | `--admin-font-control` |
| 输入框高度 | ≈32px | 动态 | Ant Input middle 高度仍 32 |
| 数字输入 / Radio / 上传按钮 | 13px | 动态 | `--admin-font-control` / 按钮 token |
| 底部取消 / 确认 | 13px / 高≈32px | 动态 | `--vben-button-font-size` |
| 校验错误提示 | 12px | 动态 | `base × 12/16`（`FormMessage`，单独档） |

---

## 对照小结

| 区域 | base=16 主字号 | 结论 |
|---|---|---|
| 搜索表单 | 13 / 12 / 11 | **动态** = `--admin-font-search*`（相对 16 的档位） |
| 默认按钮（新增/批量/表单底栏等） | 13 | **动态** = `--vben-button-font-size` = base × 13/16 |
| 表格表头/正文/分页 | ≈13 | **动态** = VXE `small` |
| 表格 Tag / Switch / 操作链接 / Tooltip | 13 | **动态** = `--admin-font-control` / 按钮 token |
| 添加/编辑表单字段 | 13（标题≈16） | **动态** = `--admin-font-control`；按钮同 13 |
| 表单校验错误提示 | 12 | **动态** = `base × 12/16`（单独档） |

---

## 相关文件

- `.cursor/rules/admin-font-size.mdc` — AI 规则
- `apps/admin/web-antd/src/preferences.ts` — 主题字号 16
- `apps/admin/packages/@core/base/design/src/design-tokens/default.css` — token 定义
- `apps/admin/packages/styles/src/antd/index.css` — 表单控件 / Tag / 按钮字号覆盖
- `apps/admin/web-antd/src/styles/grid-search-form.css` — 搜索栏引用 token
- `apps/admin/packages/effects/plugins/src/vxe-table/style.css` — 浮动标签 / 筛选面板引用 token
- `apps/admin/web-antd/src/adapter/vxe-table.ts` — 表格 `size: 'small'`
