import { makeAutoObservable, runInAction } from 'mobx';
import { QueryService } from '../services/QueryService';
import type { ParsedForm, TemplateInfo } from '../types/api';

export class TemplateStore {
  template: TemplateInfo | null = null;
  form: ParsedForm | null = null;
  loading = false;
  uploading = false;
  error: string | null = null;
  successMessage: string | null = null;

  selectedTierIndex = 0;
  quantities: Record<number, number> = {};
  clientEmail = '';
  comment = '';

  constructor() {
    makeAutoObservable(this);
  }

  public async loadTemplate(): Promise<void> {
    this.loading = true;
    this.error = null;

    try {
      const data = await QueryService.getTemplate();
      runInAction(() => {
        this.applyTemplateData(data);
      });
    } catch {
      runInAction(() => {
        this.error = 'Не удалось загрузить шаблон.';
      });
    } finally {
      runInAction(() => {
        this.loading = false;
      });
    }
  }

  public async uploadTemplate(file: File): Promise<boolean> {
    this.uploading = true;
    this.error = null;
    this.successMessage = null;

    try {
      const data = await QueryService.uploadTemplate(file);
      runInAction(() => {
        this.applyTemplateData(data);
        this.successMessage = `Шаблон «${data.template?.original_name}» загружен.`;
      });
      return true;
    } catch (error: unknown) {
      runInAction(() => {
        if (axiosLike(error)?.response?.data?.message) {
          this.error = axiosLike(error)!.response!.data!.message as string;
        } else {
          this.error = 'Не удалось загрузить файл.';
        }
      });
      return false;
    } finally {
      runInAction(() => {
        this.uploading = false;
      });
    }
  }

  public setTierIndex(index: number): void {
    this.selectedTierIndex = index;
  }

  public setQuantity(rowIndex: number, quantity: number): void {
    this.quantities[rowIndex] = Math.max(0, quantity);
  }

  public getTierMultiplier(): number {
    const tier = this.form?.tiers[this.selectedTierIndex];
    return tier?.multiplier ?? 1;
  }

  public getUnitPrice(row: { base_price: number; tier_price: number }): number {
    const tiers = this.form?.tiers ?? [];
    const baseMultiplier = tiers[0]?.multiplier || 1;
    const selectedMultiplier = this.getTierMultiplier();

    if (baseMultiplier === 0) {
      return row.base_price;
    }

    return Math.round((row.base_price * selectedMultiplier / baseMultiplier) * 100) / 100;
  }

  public get orderTotal(): number {
    if (!this.form) {
      return 0;
    }

    return this.form.groups.reduce((groupSum, group) => {
      return groupSum + group.rows.reduce((rowSum, row) => {
        const qty = this.quantities[row.row_index] ?? 0;
        return rowSum + qty * this.getUnitPrice(row);
      }, 0);
    }, 0);
  }

  private applyTemplateData(data: { template: TemplateInfo | null; form: ParsedForm | null }): void {
    this.template = data.template;
    this.form = data.form;
    this.quantities = {};
    this.selectedTierIndex = data.form?.default_tier_index ?? 0;
  }
}

function axiosLike(error: unknown): { response?: { data?: { message?: string } } } | null {
  if (typeof error === 'object' && error !== null && 'response' in error) {
    return error as { response?: { data?: { message?: string } } };
  }
  return null;
}

export const templateStore = new TemplateStore();
