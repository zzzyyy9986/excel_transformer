export interface PriceTier {
  name: string;
  multiplier: number;
}

export interface ProductRow {
  row_index: number;
  model: string;
  size: string;
  color: string;
  barcode: string;
  base_price: number;
  tier_price: number;
  quantity: number;
  description: string;
  is_group_header: boolean;
}

export interface ProductGroup {
  model_code: string;
  description: string;
  header_row_index?: number;
  image_left_url?: string | null;
  image_right_url?: string | null;
  rows: ProductRow[];
}

export interface ParsedForm {
  title: string;
  price_label: string;
  default_tier_index: number;
  tiers: PriceTier[];
  groups: ProductGroup[];
}

export interface TemplateInfo {
  id: number;
  original_name: string;
  uploaded_at: string;
}

export interface TemplateResponse {
  template: TemplateInfo | null;
  form: ParsedForm | null;
}
