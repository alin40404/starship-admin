# Starship Shop 模块框架实现方案

> 定位：普通商城解决方案 Module，**不是**万能商品中台。  
> 游戏 / 教育 / 预约等业态由独立 Module 承接。

---

## 1. 核心原则

Starship Admin 是底座，通过不同 Business Module 提供解决方案：

| 层级 | 职责 |
|------|------|
| Core | 框架扩展（Module / Plugin / Theme / Contracts） |
| System | 后台基础管理 |
| Business Module | 完整业务解决方案（Shop、GameShop…） |
| Plugin | 第三方能力（支付、短信、游戏渠道…） |

**不要**把所有业务塞进 Shop 的 `product_type` / `business_type` 枚举。

---

## 2. Module 边界（本期实现）

### 2.1 `modules/Shop` — 普通商城

适用：手机、服装、食品、数码、会员卡密、PDF/素材包等。

商品类型仅：

| 值 | 说明 | 典型交付 |
|----|------|----------|
| `physical` | 实物 | 物流配送 |
| `virtual` | 虚拟（会员卡等） | 自动发放 / 卡券 / 兑换码 / 人工 |
| `digital` | 数字资源（PDF、素材） | 自动发放 / 兑换码 / 人工 |

能力：分类、品牌、SPU/SKU、库存、购物车、订单、支付、发货/卡密履约。

履约 / 库存契约位于 `modules/Shop/app/Contracts/`（`FulfillmentInterface`、`InventoryInterface`），**不进入 Core**。

**不包含**：游戏区服角色、课程章节、预约排班、订阅计费。

### 2.2 `modules/GameShop` — 游戏商城（独立，不依赖 Shop）

适用：点券直充、Steam CDKEY、游戏道具。

自有表：

- `game_shop_games` — 游戏 / 平台 / 厂商
- `game_shop_products` — 游戏商品（topup / cdkey / item）
- `game_shop_orders` — 游戏订单（账号、区服、角色快照）

充值渠道走 Plugin（如 `plugins/game-provider`），不写死在 Module 内。

后台：游戏管理 / 游戏商品 / 游戏订单。

---

## 3. 未来 Module（规划，不现在做）

| Module | 定位 |
|--------|------|
| Education | 课程、学员、考试、证书 |
| O2O | 服务项目、门店、排班、预约、核销 |
| Subscription / Billing / License / Tenant | 订阅与 SaaS 底座能力，不是「SaaS 业务 Module」 |

判断规则：

1. 只是卖一个东西（电子书、激活码）→ Shop  
2. 有独立业务流程（课程进度考试）→ 独立 Module  
3. 有行业特殊对象（游戏区服角色）→ 独立 Module（如 GameShop）

---

## 4. Shop 商品字段

```text
category_id       商品目录分类
brand_id          品牌（可选）
product_type      physical | virtual | digital
fulfillment_type  shipping | digital | card | code | manual
extra             可选 JSON，不做万能扩展槽 UI
```

已删除：`business_type`、service/subscription/appointment 等业态枚举。

---

## 5. 目录关系

```text
modules/
  Shop/          # 普通商城
  GameShop/      # 游戏商城（并列，不依赖 Shop）
  System/
  Cms/
  …

plugins/
  payment/
  game-provider/ # 未来
```

---

## 6. 开发顺序

1. Core / System / Plugin / Theme 机制（已有）  
2. **Shop** 普通商城闭环（本期）  
3. **GameShop** 骨架 + 后台 CRUD（本期）  
4. GameShop 支付与充值 Plugin、Education / O2O 等按需扩展  

---

## 7. 一句话

> Starship Admin 不做包含所有业务的超级平台，而是提供稳定底座，让 Shop、GameShop 等业务 Module 独立生长。
