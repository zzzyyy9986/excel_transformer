import { observer } from 'mobx-react-lite';
import type { FormEvent } from 'react';
import type { ProductGroup } from '../types/api';
import { templateStore } from '../store/TemplateStore';

export const OrderFormTab = observer(function OrderFormTab({
  onSubmitted,
}: {
  onSubmitted?: () => void;
}) {
  const { form, template } = templateStore;

  if (!template || !form) {
    return (
      <div className="alert alert-warning">
        Шаблон не загружен. Перейдите на вкладку «Загрузить шаблон» и загрузите Excel-файл.
      </div>
    );
  }

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    const ok = await templateStore.submitOrder();
    if (ok) {
      onSubmitted?.();
    }
  };

  return (
    <form onSubmit={(event) => void handleSubmit(event)} className="order-form">
      <p className="order-form__instruction">
        1, заполните необходимые ячейки для заказа и нажмите «Отправить заказ»
      </p>

      <div className="order-form__meta table-responsive">
        <table className="table table-bordered order-meta-table mb-0">
          <tbody>
            <tr>
              <td className="order-meta-table__label">{form.price_label}</td>
              <td className="order-meta-table__tier">
                <select
                  id="price-tier"
                  className="form-select form-select-sm"
                  value={templateStore.selectedTierIndex}
                  onChange={(event) => templateStore.setTierIndex(Number(event.target.value))}
                >
                  {form.tiers.map((tier, index) => (
                    <option key={tier.name} value={index}>
                      {tier.name}
                    </option>
                  ))}
                </select>
              </td>
              <td className="order-meta-table__sum-label">Сумма заказа:</td>
              <td className="order-meta-table__sum-value">{templateStore.orderTotal.toFixed(2)}</td>
            </tr>
            <tr>
              <td colSpan={2} className="fw-semibold">{form.title}</td>
              <td className="text-muted">Ваш прайс:</td>
              <td>{form.tiers[templateStore.selectedTierIndex]?.name ?? ''}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div className="table-responsive mt-3">
        <table className="table table-bordered order-table align-middle mb-0">
          <thead className="table-light">
            <tr>
              <th className="order-table__photo-col" />
              <th>модель</th>
              <th>размер</th>
              <th>цвет</th>
              <th className="text-end">цена</th>
              <th className="text-center order-table__order-col">заказ</th>
              <th>описание</th>
              <th className="order-table__photo-col" />
            </tr>
          </thead>
          <tbody>
            {form.groups.map((group) => (
              <GroupRows key={group.model_code} group={group} />
            ))}
          </tbody>
        </table>
      </div>

      <div className="order-form__footer mt-4">
        <div className="row g-3">
          <div className="col-md-6">
            <label className="form-label" htmlFor="order-comment">
              Комментарий
            </label>
            <textarea
              id="order-comment"
              className="form-control"
              rows={3}
              value={templateStore.comment}
              onChange={(event) => {
                templateStore.comment = event.target.value;
              }}
            />
          </div>
          <div className="col-md-6">
            <label className="form-label" htmlFor="client-contact">
              Как с вами связаться?
            </label>
            <input
              id="client-contact"
              type="text"
              className="form-control"
              placeholder="Email, телефон или Telegram"
              value={templateStore.clientEmail}
              onChange={(event) => {
                templateStore.clientEmail = event.target.value;
              }}
            />
          </div>
        </div>

        <div className="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
          <div className="order-form__footer-sum">
            Сумма заказа: <strong>{templateStore.orderTotal.toFixed(2)}</strong>
          </div>
          <button type="submit" className="btn btn-primary btn-lg px-5" disabled={templateStore.submitting}>
            {templateStore.submitting ? 'Отправка…' : 'Отправить заказ'}
          </button>
        </div>
      </div>
    </form>
  );
});

const GroupRows = observer(function GroupRows({ group }: { group: ProductGroup }) {
  const rowSpan = group.rows.length;
  const description = group.description || group.rows.find((row) => row.description)?.description || '';

  return (
    <>
      {group.rows.map((row, index) => (
        <tr key={row.row_index} className={index === 0 ? 'order-table__group-start' : ''}>
          {index === 0 && (
            <td rowSpan={rowSpan} className="order-table__photo-cell">
              {group.image_left_url ? (
                <img src={group.image_left_url} alt={group.model_code} className="order-table__photo" />
              ) : null}
            </td>
          )}
          <td className="order-table__model">{row.model}</td>
          <td>{row.size}</td>
          <td>{row.color}</td>
          <td className="text-end">{templateStore.getUnitPrice(row).toFixed(0)}</td>
          <td className="text-center">
            <input
              type="number"
              min={0}
              step={1}
              className="form-control form-control-sm text-center order-table__qty-input"
              value={templateStore.quantities[row.row_index] ?? ''}
              onChange={(event) =>
                templateStore.setQuantity(row.row_index, Number(event.target.value) || 0)
              }
            />
          </td>
          {index === 0 && (
            <td rowSpan={rowSpan} className="order-table__description">
              {description ? <DescriptionCell text={description} /> : null}
            </td>
          )}
          {index === 0 && (
            <td rowSpan={rowSpan} className="order-table__photo-cell">
              {group.image_right_url ? (
                <img src={group.image_right_url} alt={group.model_code} className="order-table__photo" />
              ) : null}
            </td>
          )}
        </tr>
      ))}
    </>
  );
});

function DescriptionCell({ text }: { text: string }) {
  const parts = text.split('\n\n');
  const lastPart = parts[parts.length - 1] ?? '';
  const hasLink = lastPart.includes('Посмотреть') || lastPart.startsWith('http');
  const bodyParts = hasLink ? parts.slice(0, -1) : parts;
  const linkText = hasLink ? lastPart : null;

  return (
    <div className="order-table__description-text">
      {bodyParts.join('\n\n')}
      {linkText && (
        <div className="mt-2">
          {linkText.startsWith('http') ? (
            <a href={linkText} target="_blank" rel="noreferrer">
              {linkText}
            </a>
          ) : (
            <span className="text-primary">{linkText}</span>
          )}
        </div>
      )}
    </div>
  );
}
